<?php
namespace Hexasoft\FraudLabsPro\Block\Adminhtml\Order\View;

class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info {
	protected $registry;
	protected $_order;
	protected $scopeConfig;
	protected $_objectManager;

	public function __construct(\Magento\Framework\Registry $registry, \Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig){
		$this->registry = $registry;
		$this->_objectManager = $objectManager;
		$this->scopeConfig = $scopeConfig;
		$this->_order = $this->registry->registry('current_order');
	}

	protected function _getCollectionClass(){
		return 'directory/country';
	}

	public function toHtml(){
		$rejectStatus = $this->scopeConfig->getValue('fraudlabspro/active_display/reject_status',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);

		$order = $this->_order;

		if(!empty($order)){
			if(is_null(json_decode($order->getfraudlabspro_response(), true))){
				if($order->getfraudlabspro_response()){
					$data = $this->_unserialize($order->getfraudlabspro_response());
				}
			} else {
				$data = json_decode($order->getfraudlabspro_response(), true);
			}
		}

		if(filter_input(INPUT_GET, 'approve') || filter_input(INPUT_GET, 'reject') || filter_input(INPUT_GET, 'reject-blacklist')){
			$data['fraudlabspro_status'] = (filter_input(INPUT_GET, 'approve')) ? 'APPROVE' : 'REJECT';
			$action = (filter_input(INPUT_GET, 'approve')) ? 'APPROVE' : ((filter_input(INPUT_GET, 'reject')) ? 'REJECT' : 'REJECT_BLACKLIST');
			$apiKey = filter_input(INPUT_GET, 'apiKey');
			$flpId = filter_input(INPUT_GET, 'flpId');

			$this->_get('https://api.fraudlabspro.com/v1/order/feedback?' . http_build_query(array(
				'format'	=> 'json',
				'key'		=> $apiKey,
				'action'	=> $action,
				'id'		=> $flpId,
			)));

			$order->setfraudlabspro_response(json_encode($data))->save();
		}

		if(!isset($data))
			return '
			<div class="entry-edit">
				<div class="entry-edit-head" style="background:#cc0000;">
					<h4 class="icon-head head-shipping-method"><a href="http://www.fraudlabspro.com" target="_blank"><img src="http://www.fraudlabspro.com/images/logo-small.png" width="163" height="20" border="0" align="absMiddle" /></a></h4>
				</div>

				<fieldset>
					This order is not processed by FraudLabs Pro.
				</fieldset>
			</div>';

		$plan_name = '';
		$apiKeyPlan = $this->scopeConfig->getValue('fraudlabspro/active_display/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if($apiKeyPlan !== ''){
			$responsePlan = $this->_get('https://api.fraudlabspro.com/v1/plan?key=' . rawurlencode($apiKeyPlan) . '&format=json');
			$resultPlan = json_decode($responsePlan, true);
			$plan_name = $resultPlan['plan_name'];
		}

		if($data['fraudlabspro_score'] > 80){
			$score = '<div style="color:#FF0000;font-size:4em;margin-top:20px;"><strong>'.$data['fraudlabspro_score'].'</strong></div>';
		}
		elseif($data['fraudlabspro_score'] > 60){
			$score = '<div style="color:#FFCC00;font-size:4em;margin-top:20px;"><strong>'.$data['fraudlabspro_score'].'</strong></div>';
		}
		elseif($data['fraudlabspro_score'] > 40){
			$score = '<div style="color:#ffc166;font-size:4em;margin-top:20px;"><strong>'.$data['fraudlabspro_score'].'</strong></div>';
		}
		elseif($data['fraudlabspro_score'] > 20){
			$score = '<div style="color:#66CC66;font-size:4em;margin-top:20px;"><strong>'.$data['fraudlabspro_score'].'</strong></div>';
		}
		else{
			$score = '<div style="color:#33CC00;font-size:3em;margin-top:20px;"><strong>'.$data['fraudlabspro_score'].'</strong></div>';
		}

		$countryName = $this->_objectManager->create('Magento\Directory\Model\Country')->load($data['ip_country'])->getName();
		$location = array($data['ip_continent'], $countryName, $data['ip_region'], $data['ip_city']);
		$location = array_unique($location);

		switch($data['fraudlabspro_status']){
			case 'REVIEW':
				$status = '<div style="color:#FFCC00;font-size:2em;margin-top:10px;"><strong>'.$data['fraudlabspro_status'].'</strong></div>';
			break;

			case 'REJECT':
				$status = '<div style="color:#cc0000;font-size:2em;margin-top:10px;"><strong>'.$data['fraudlabspro_status'].'</strong></div>';
			break;

			case 'APPROVE':
				$status = '<div style="color:#336600;font-size:2em;margin-top:10px;"><strong>'.$data['fraudlabspro_status'].'</strong></div>';
			break;

			default:
				$status = '-';
		}

		$out = '
		<div class="entry-edit">
			<div class="entry-edit-head" style="background:#cc0000; padding:5px;">
				<h4 class="icon-head head-shipping-method"><a href="http://www.fraudlabspro.com" target="_blank"><img src="http://www.fraudlabspro.com/images/logo-small.png" width="163" height="20" border="0" align="absMiddle" /></a></h4>
			</div>

			<fieldset>
			<table width="100%" border="1" bordercolor="#c0c0c0" style="border-collapse:collapse;">
			<tr>
				<td rowspan="3" style="width:90px; text-align:center; vertical-align:top; padding:5px;"><strong>Score</strong> <a href="javascript:;" title="Overall score between 0 and 100. 100 is the highest risk. 0 is the lowest risk.">[?]</a><br/>' . $score . '</td>
				<td style="width:120px; padding:5px;"><span><strong>IP Address</strong></span></td>
				<td style="width:150px; padding:5px;"><span>' . $data['ip_address'] . '</span></td>
				<td style="width:140px; padding:5px;"><span><strong>IP Net Speed</strong> <a href="javascript:;" title="Connection speed.">[?]</a></span></td>
				<td style="width:120px; padding:5px;"><span>' . $data['ip_netspeed'] . '</span></td>
				<td style="width:120px; padding:5px;"><span><strong>IP ISP Name</strong> <a href="javascript:;" title="Estimated ISP of the IP address.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . $data['ip_isp_name'] . '</span></td>
			</tr>
			<tr>
				<td style="padding:5px;"><span><strong>IP Usage Type</strong> <a href="javascript:;" title="Estimated usage type of the IP address. For example: ISP, Commercial, Residential.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . $data['ip_usage_type'] . '</span></td>
				<td style="padding:5px;"><span><strong>IP Domain</strong> <a href="javascript:;" title="Estimated domain name of the IP address.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . $data['ip_domain'] . '</span></td>
				<td style="padding:5px;"><span><strong>IP Time Zone</strong> <a href="javascript:;" title="Estimated time zone of the IP address.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . $data['ip_timezone'] . '</span></td>
			</tr>
			<tr>
				<td style="padding:5px;"><span><strong>IP Location</strong> <a href="javascript:;" title="Estimated location of the IP address.">[?]</a></span></td>
				<td colspan="3" style="padding:5px;"><span>' . implode(', ', $location) . ' <a href="http://www.geolocation.com/' . $data['ip_address'] . '" target="_blank">[Map]</a></span></td>
				<td style="padding:5px;"><span><strong>IP Distance</strong> <a href="javascript:;" title="Distance from IP address to Billing Location.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . $data['distance_in_mile'] . ' Miles</span></td>
			</tr>
			<tr>
				<td rowspan="4"  style="padding:5px; vertical-align:top; text-align:center;"><span><strong>FraudLabs Pro Status</strong> <a href="javascript:;" title="FraudLabs Pro status.">[?]</a><br>' . $status . '</span></td>
				<td style="padding:5px;"><span><strong>IP Latitude</strong> <a href="javascript:;" title="Estimated latitude of the IP address.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . $data['ip_latitude'] . '</span></td>
				<td style="padding:5px;"><span><strong>IP Longitude</strong> <a href="javascript:;" title="Estimated longitude of the IP address.">[?]</a></span></td>
				<td colspan="3" style="padding:5px;"><span>' . $data['ip_longitude'] . '</span></td>
			</tr>
			<tr>
				<td style="padding:5px;"><span><strong>Risk Country</strong> <a href="javascript:;" title="Whether IP address or billing address country is in the latest high risk list.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . (($data['is_high_risk_country'] == 'Y') ? 'Yes' : (($data['is_high_risk_country'] == 'N') ? 'No' : '-')) . '</span></td>
				<td style="padding:5px;"><span><strong>Free Email</strong> <a href="javascript:;" title="Whether e-mail is from free e-mail provider.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . (($data['is_free_email'] == 'Y') ? 'Yes' : (($data['is_free_email'] == 'N') ? 'No' : '-')) . '</span></td>
				<td style="padding:5px;"><span><strong>Ship Forward</strong> <a href="javascript:;" title="Whether shipping address is in database of known mail drops.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . (($data['is_address_ship_forward'] == 'Y') ? 'Yes' : (($data['is_address_ship_forward'] == 'N') ? 'No' : '-')) . '</span></td>
			</tr>
			<tr>
				<td style="padding:5px;"><span><strong>Using Proxy</strong> <a href="javascript:;" title="Whether IP address is from Anonymous Proxy Server.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . (($data['is_proxy_ip_address'] == 'Y') ? 'Yes' : 'No') . '</span></td>
				<td style="padding:5px;"><span><strong>BIN Found</strong> <a href="javascript:;" title="Whether the BIN information matches our BIN list.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . (($data['is_bin_found'] == 'Y') ? 'Yes' : (($data['is_bin_found'] == 'N') ? 'No' : '-')) . '</span></td>
				<td style="padding:5px;"><span><strong>Triggered Rules</strong> <a href="javascript:;" title="FraudLabs Pro Rules triggered.">[?]</a></span></td>
				<td style="padding:5px;"><span>' . (strpos($plan_name, 'Micro') ? '<span style="color:orange">Available for <a href="https://www.fraudlabspro.com/pricing" target="_blank">Mini plan</a> onward. Please <a href="https://www.fraudlabspro.com/merchant/login" target="_blank">upgrade</a>.</span>' : ((isset($data['fraudlabspro_rules'])) ? $data['fraudlabspro_rules'] : '-' )) . '</span></td>
			</tr>
			<tr>
				<td style="padding:5px;"><span><strong>Email Blacklist</strong> <a href="javascript:;" title="Whether the email address is in our blacklist database.">[?]</a></span></td>
				<td style="padding:5px;"><span>' .  (($data['is_email_blacklist'] == 'Y') ? 'Yes' : (($data['is_email_blacklist'] == 'N') ? 'No' : '-')) . '</span></td>
				<td style="padding:5px;"><span><strong>Credit Card Blacklist</strong> <a href="javascript:;" title="Whether the credit card is in our blacklist database.">[?]</a></span></td>
				<td style="padding:5px;"><span>' .  (($data['is_credit_card_blacklist'] == 'Y') ? 'Yes' : (($data['is_credit_card_blacklist'] == 'N') ? 'No' : '-')) . '</span></td>
				<td style="padding:5px;"><span><strong>Phone Verified</strong> <a href="javascript:;" title="Whether the phone address is verified by the customer.">[?]</a></span></td>
				<td style="padding:5px;"><span>' .  (isset($data['is_phone_verified']) ? $data['is_phone_verified'] : 'NA') . '</span></td>
			</tr>
			<tr>
				<td style="padding:5px;"><span><strong>Message</strong> <a href="javascript:;" title="FraudLabs Pro service message response.">[?]</a></span></td>
				<td colspan="6" style="padding:5px;"><span>' . (($data['fraudlabspro_error_code']) ? $data['fraudlabspro_error_code'] . ': ' : '') . $data['fraudlabspro_message'] . '</span></td>
			</tr>
			<tr>
				<td style="padding:5px;"><span><strong>Link</strong></span></td>
				<td colspan="6" style="padding:5px;"><span><a href="https://www.fraudlabspro.com/merchant/transaction-details/' . $data['fraudlabspro_id'] . '" target="_blank">https://www.fraudlabspro.com/merchant/transaction-details/' . $data['fraudlabspro_id'] . '</a></span></td>
			</tr>';

		if($data['fraudlabspro_status'] == 'REVIEW'){
			$out .= '
			<tr>
				<td colspan="7">
					<form id="review-action">
						<input type="hidden" name="apiKey" value="' . $data['api_key'] . '" />
						<input type="hidden" name="flpId" value="' . $data['fraudlabspro_id'] . '" />

						<div style="text-align:center;padding:10px">
							<input type="submit" name="approve" value="Approve Order" />
							<input type="submit" name="reject" value="Reject Order" />
							<input type="submit" name="reject-blacklist" value="Blacklist Order" />
						</div>
					</form>
				</td>
			</tr>';
		}

		$out .= '
				</table>
			</fieldset>
		</div>';

		return $out;
	}

	private function _get($url){

		 $ch = curl_init();

		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_ENCODING , 'gzip, deflate');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$result = curl_exec($ch);

		if(!curl_errno($ch)) return $result;

		curl_close($ch);

		return false;
	}

	private function _unserialize($data){
		if (class_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$serializer = $objectManager->create(\Magento\Framework\Serialize\SerializerInterface::class);
			return $serializer->unserialize($data);
		} else if (class_exists(\Magento\Framework\Unserialize\Unserialize::class)) {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$serializer = $objectManager->create(\Magento\Framework\Unserialize\Unserialize::class);
			return $serializer->unserialize($data);
		}
	}
}
