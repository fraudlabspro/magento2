<?php
namespace Hexasoft\FraudLabsPro\Block\Adminhtml\Order\View;

use Magento\Framework\Escaper;

class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info {
	protected $registry;
	protected $_order;
	protected $scopeConfig;
	protected $_objectManager;
	protected $escaper;

	public function __construct(\Magento\Framework\Registry $registry, \Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, Escaper $escaper){
		$this->registry = $registry;
		$this->_objectManager = $objectManager;
		$this->scopeConfig = $scopeConfig;
		$this->_order = $this->registry->registry('current_order');
		$this->escaper = $escaper;
	}

	protected function _getCollectionClass(){
		return 'directory/country';
	}

	public function toHtml(){
		$approveStatus = $this->scopeConfig->getValue('fraudlabspro/active_display/approve_status',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$rejectStatus = $this->scopeConfig->getValue('fraudlabspro/active_display/reject_status',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);

		$order = $this->_order;

		$out = '';

		if(!empty($order)){
			$data = json_decode($order->getfraudlabspro_response(), true);
		}

		if(filter_input(INPUT_GET, 'flp-approve') || filter_input(INPUT_GET, 'flp-reject') || filter_input(INPUT_GET, 'flp-reject-blacklist')){
			$data['fraudlabspro_status'] = (filter_input(INPUT_GET, 'flp-approve')) ? 'APPROVE' : 'REJECT';
			$action = (filter_input(INPUT_GET, 'flp-approve')) ? 'APPROVE' : ((filter_input(INPUT_GET, 'flp-reject')) ? 'REJECT' : 'REJECT_BLACKLIST');
			$apiKey_raw = filter_input(INPUT_GET, 'apiKey');
			$apiKey = preg_replace('/[^a-zA-Z0-9]/', '', $apiKey_raw);
			$flpId_raw = filter_input(INPUT_GET, 'flpId');
			$flpId = preg_replace('/[^a-zA-Z0-9-]/', '', $flpId_raw);

			$queries= [
				'format'		=> 'json',
				'key'			=> $apiKey,
				'action'		=> $action,
				'id'			=> $flpId,
				'source'		=> 'magento',
				'triggered_by'	=> 'manual',
			];

			$this->_post('https://api.fraudlabspro.com/v2/order/feedback', $queries);

			$order->setfraudlabspro_response(json_encode($data))->save();

			if(filter_input(INPUT_GET, 'flp-approve')){
				switch ($approveStatus) {
					case 'pending':
						$order->setState(\Magento\Sales\Model\Order::STATE_NEW, true)->save();
						$order->setStatus('pending', true)->save();
						break;

					case 'processing':
						$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
						break;

					case 'complete':
						$order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE, true)->save();
						break;

					case 'closed':
						$order->setState(\Magento\Sales\Model\Order::STATE_CLOSED, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED, true)->save();
						break;

					case 'fraud':
						$order->setState(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
						break;

					case 'canceled':
						if ($order->canCancel()) {
							$order->cancel()->save();
						}
						break;

					case 'holded':
						$order->setHoldBeforeState($order->getState());
						$order->setHoldBeforeStatus($order->getStatus());
						$order->setState(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
						break;
				}
				$out .= "<script>
window.onload = function() {
	if(!window.location.hash) {
		window.location = window.location + '#loaded';
		window.location.reload();
	}
}</script>";
			}
			elseif(filter_input(INPUT_GET, 'flp-reject') || filter_input(INPUT_GET, 'flp-reject-blacklist')){
				switch ($rejectStatus) {
					case 'pending':
						$order->setState(\Magento\Sales\Model\Order::STATE_NEW, true)->save();
						$order->setStatus('pending', true)->save();
						break;

					case 'processing':
						$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
						break;

					case 'complete':
						$order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE, true)->save();
						break;

					case 'closed':
						$order->setState(\Magento\Sales\Model\Order::STATE_CLOSED, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED, true)->save();
						break;

					case 'fraud':
						$order->setState(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
						break;

					case 'canceled':
						if ($order->canCancel()) {
							$order->cancel()->save();
						}
						break;

					case 'holded':
						$order->setHoldBeforeState($order->getState());
						$order->setHoldBeforeStatus($order->getStatus());
						$order->setState(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
						$order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
						break;
				}
				$out .= "<script>
window.onload = function() {
	if(!window.location.hash) {
		window.location = window.location + '#loaded';
		window.location.reload();
	}
}</script>";
			}
		}

		if(!isset($data))
			return '
			<div class="entry-edit">
				<div class="entry-edit-head" style="background:#cc0000;">
					<h4 class="icon-head head-shipping-method"><a href="https://www.fraudlabspro.com" target="_blank"><img src="https://www.fraudlabspro.com/images/logo-small.png" width="163" height="20" border="0" align="absMiddle" /></a></h4>
				</div>

				<fieldset>
					This order is not processed by FraudLabs Pro.
				</fieldset>
			</div>';

		$plan_name = '';
		$apiKeyPlan = $this->scopeConfig->getValue('fraudlabspro/active_display/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if($apiKeyPlan !== ''){
			$responsePlan = $this->_get('https://api.fraudlabspro.com/v2/plan/result?key=' . rawurlencode($apiKeyPlan) . '&format=json');
			$resultPlan = json_decode($responsePlan, true);
			$plan_name = $resultPlan['plan_name'];
		}
		$flpErrCode = ($data['error']['error_code'] ?? '');
		$flpErrMsg = ($data['error']['error_message'] ?? '-');

		if($data['fraudlabspro_score'] > 80){
			$score = '<div style="color:#FF0000;font-size:4em;margin-top:20px;"><strong>'. $this->escaper->escapeHtml($data['fraudlabspro_score']) .'</strong></div>';
		}
		elseif($data['fraudlabspro_score'] > 60){
			$score = '<div style="color:#FFCC00;font-size:4em;margin-top:20px;"><strong>'. $this->escaper->escapeHtml($data['fraudlabspro_score']) .'</strong></div>';
		}
		elseif($data['fraudlabspro_score'] > 40){
			$score = '<div style="color:#ffc166;font-size:4em;margin-top:20px;"><strong>'. $this->escaper->escapeHtml($data['fraudlabspro_score']) .'</strong></div>';
		}
		elseif($data['fraudlabspro_score'] > 20){
			$score = '<div style="color:#66CC66;font-size:4em;margin-top:20px;"><strong>'. $this->escaper->escapeHtml($data['fraudlabspro_score']) .'</strong></div>';
		}
		else{
			$score = '<div style="color:#33CC00;font-size:3em;margin-top:20px;"><strong>'. $this->escaper->escapeHtml($data['fraudlabspro_score']) .'</strong></div>';
		}

		$countryCode = (($data['ip_geolocation']['country_code']) ?? ($data['ip_country'] ?? ''));
		$region = (($data['ip_geolocation']['region']) ?? ($data['ip_region'] ?? ''));
		$city = (($data['ip_geolocation']['city']) ?? ($data['ip_city'] ?? ''));
		$countryName = $this->_objectManager->create('Magento\Directory\Model\Country')->load($countryCode)->getName();
		$location = array($countryName, $region, $city);
		$location = array_unique($location);

		switch($data['fraudlabspro_status']){
			case 'REVIEW':
				$status = '<div style="color:#FFCC00;font-size:2em;margin-top:10px;"><strong>'. $this->escaper->escapeHtml($data['fraudlabspro_status']) .'</strong></div>';
			break;

			case 'REJECT':
				$status = '<div style="color:#cc0000;font-size:2em;margin-top:10px;"><strong>'. $this->escaper->escapeHtml($data['fraudlabspro_status']) .'</strong></div>';
			break;

			case 'APPROVE':
				$status = '<div style="color:#336600;font-size:2em;margin-top:10px;"><strong>'. $this->escaper->escapeHtml($data['fraudlabspro_status']) .'</strong></div>';
			break;

			default:
				$status = '-';
		}

		$usageType = (($data['ip_geolocation']['usage_type']) ?? ($data['ip_usage_type'] ?? 'NA'));
		$usageType = is_array($usageType) ? implode(', ', $usageType) : $usageType;
		$timezone = (($data['ip_geolocation']['timezone']) ?? ($data['ip_timezone'] ?? ''));
		$distanceKm = (($data['billing_address']['ip_distance_in_km']) ?? ($data['distance_in_km'] ?? ''));
		$distanceMile = (($data['billing_address']['ip_distance_in_mile']) ?? ($data['distance_in_mile'] ?? ''));
		$lat = (($data['ip_geolocation']['latitude']) ?? ($data['ip_latitude'] ?? ''));
		$lon = (($data['ip_geolocation']['longitude']) ?? ($data['ip_longitude'] ?? ''));
		$shipForward = (($data['shipping_address']['is_address_ship_forward']) ?? ($data['is_address_ship_forward'] ?? ''));
		$freeEmail = (($data['email_address']['is_free']) ?? ($data['is_free_email'] ?? ''));
		$proxyIP = (($data['ip_geolocation']['is_proxy']) ?? ($data['is_proxy_ip_address'] ?? ''));
		$blacklistIP = (($data['ip_geolocation']['is_in_blacklist']) ?? ($data['is_ip_blacklist'] ?? ''));
		$blacklistEmail = (($data['email_address']['is_in_blacklist']) ?? ($data['is_email_blacklist'] ?? ''));
		$flpRule = '-';
		if (isset($data['fraudlabspro_rules'])) {
			if (is_array($data['fraudlabspro_rules'])) {
				$flpRule = implode(', ', $data['fraudlabspro_rules']);
			} else {
				$flpRule = $data['fraudlabspro_rules'];
			}
		}
		if ($flpRule == "") {
			$flpRule = '-';
		}

		$out .= '
			<div style="padding:10px 20px;border: 0.75px solid #cccccc;border-radius: 5px;box-shadow: 0.125rem .25rem rgba(0, 0, 0, .075) !important;margin-bottom:40px;">
				<h1 style="font-size:20px;">FraudLabs Pro Fraud Validation Result</h1>
				<p style="font-size:17px;color:#181f2d;font-weight:600;margin-top:20px;">General</p>
				<div style="margin-bottom:15px;text-align:center;">
					<img style="margin-top:10px;margin-bottom:0;" src="https://cdn.fraudlabspro.com/assets/img/scores/score-' . $data['fraudlabspro_score'] . '.png" width="260" height="113" alt="77">
				</div>
				<table width="100%" style="border-collapse:collapse;text-align:left;">
					<tr style="margin-bottom:10px;vertical-align:top;">
						<td width="19.5%">&nbsp;</td>
						<td>
							<p style="color:#87888d;margin-bottom:0;margin-top:0;">FraudLabs Pro Score
								<a href="javascript:;" style="vertical-align:middle;" title="Risk score, 0 (low risk) - 100 (high risk)."> 
									<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#87888d" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247m2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/></svg>
								</a>
							</p>
							<p style="color:;margin-top:4px; margin-bottom:5px;font-weight:600;font-size:25px;">' . $this->escaper->escapeHtml($data['fraudlabspro_score']) . '</p>
						</td>
						<td>
							<p style="color:#87888d;margin-bottom:0;margin-top:0;">FraudLabs Pro Status
								<a href="javascript:;" style="vertical-align:middle;" title="FraudLabs Pro status which is either Approve, Review or Reject."> 
									<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#87888d" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247m2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/></svg>
								</a>
							</p>
							<p style="color:#FFCC00;margin-top:4px; margin-bottom:5px;font-weight:600;font-size:25px;">' . $status . '</p>
						</td>
						<td>&nbsp;</td>
					</tr>
				</table>

				<p style="font-size:17px;color:#181f2d;font-weight:600;margin-top:40px;">IP Geolocation</p>
				<div style="overflow-x:auto;">
				<table width="100%"  style="border-collapse:collapse;text-align:left;">
					<tr style="margin-bottom:10px;vertical-align:top;">
						<td width="20%">
							<p style="color:#87888d;margin-bottom:0;margin-top:0;">IP Address</p>
							<p style="color:;margin-top:4px; margin-bottom:5px;">' . $this->escaper->escapeHtml($data['ip_address']) . '</p>
						</td>
						<td width="20%">
							<p style="color:#87888d;margin-bottom:0;margin-top:0;">Coordinates</p>
							<p style="color:;margin-top:4px; margin-bottom:5px;">' . $this->escaper->escapeHtml($lat) . ', ' . $this->escaper->escapeHtml($lon) . '</p>
						</td>
						<td colspan="2" width="50%">
							<p style="color:#87888d;margin-bottom:0;margin-top:0;">IP Location</p>
							<p style="color:;margin-top:4px; margin-bottom:5px;"><a href="https://www.geolocation.com/' . $this->escaper->escapeHtml($data['ip_address']) . '" target="_blank">' . $this->escaper->escapeHtml(implode(', ', $location)) . '</a></p>
						</td>
					</tr>
					<tr style="vertical-align:top;">
						<td width="20%">
							<p style="color:#87888d;margin-bottom:0;">Time Zone</p>
							<p style="color:;margin-top:4px; margin-bottom:0;">' . $this->escaper->escapeHtml($timezone) . '</p>
						</td>
						<td width="20%">
							<p style="color:#87888d;margin-bottom:0;">Usage Type
								<a href="/" style="vertical-align:middle;" title="Usage type of the IP address. E.g, ISP, Commercial, Residential.">
									<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#87888d" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247m2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/></svg>
								</a>
							</p>
							<p style="color:;margin-top:4px; margin-bottom:0;">
								' . ( ($usageType == 'NA' ) ? '<a href="https://www.fraudlabspro.com/pricing" target="_blank">Upgrade to View »</a>' : $this->escaper->escapeHtml($usageType) ) . '
							</p>
						</td>
						<td width="20%">
							<p style="color:#87888d;margin-bottom:0;">Ship Forwarder
								<a href="javascript:;" style="vertical-align:middle;" title="Whether shipping address is a freight forwarder address.">
									<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#87888d" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247m2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/></svg>
								</a>
							</p>
							<p style="color:;margin-top:4px; margin-bottom:0;">' . $this->escaper->escapeHtml((($shipForward) ? 'Yes' : 'No')) . '</p>
						</td>
						<td>
							<p style="color:#87888d;margin-bottom:0;">IP to Billing Distance</p>
							<p style="color:;margin-top:4px; margin-bottom:0;">' . ( ( $distanceKm ) ? ( $this->escaper->escapeHtml($distanceKm) . ' KM / ' . $this->escaper->escapeHtml($distanceMile) . ' Miles' ) : '-' ) . '</p>
						</td>
					</tr>
				</table>
				</div>

				<p style="font-size:17px;color:#181f2d;font-weight:600;margin-top:45px;">Validation Information</p>
				<div style="overflow-x:auto;">
				<table width="100%"  style="border-collapse:collapse;text-align:left">
					<tr style="margin-bottom:10px;vertical-align:top;">
						<td width="20%">
							<p style="color:#87888d;margin-bottom:0;margin-top:0;">Free Email Domain</p>
							<p style="color:;margin-top:4px; margin-bottom:5px;">' . $this->escaper->escapeHtml((($freeEmail) ? 'Yes' : 'No')) . '</p>
						</td>
						<td width="20%">
							<p style="color:#87888d;margin-bottom:0;margin-top:0;">Proxy IP
								<a href="javascript:;" style="vertical-align:middle;" title="Whether IP address is from Anonymous Proxy Server."> 
									<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#87888d" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247m2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/></svg>
								</a>
							</p>
							<p style="color:;margin-top:4px; margin-bottom:5px;">' . $this->escaper->escapeHtml((($proxyIP) ? 'Yes' : 'No')) . '</p>
						</td>
						
						<td colspan="2" width="50%">
							<p style="color:#87888d;margin-bottom:0;margin-top:0;">Phone Verified</p>
							<p style="color:;margin-top:4px; margin-bottom:5px;">
								' .  (isset($data['is_phone_verified']) ? $this->escaper->escapeHtml($data['is_phone_verified']) : '<a href="https://marketplace.magento.com/hexasoft-module-fraudlabsprosmsverification.html" target="_blank">SMS Verification Extension Required</a>') . '
							</p>
						</td>
					</tr>
					<tr style="margin-bottom:10px;vertical-align:top;">
						<td>
							<p style="color:#87888d;margin-bottom:0;">IP in Blacklist</p>
							<p style="color:;margin-top:4px; margin-bottom:0;">' .  $this->escaper->escapeHtml((($blacklistIP) ? 'Yes' : 'No')) . '</p>
						</td>
						<td>
							<p style="color:#87888d;margin-bottom:0;">Email in Blacklist</p>
							<p style="color:;margin-top:4px; margin-bottom:0;">' .  $this->escaper->escapeHtml((($blacklistEmail) ? 'Yes' : 'No')) . '</p>
						</td>
						<td colspan="2">
							<p style="color:#87888d;margin-bottom:0;">Rules Triggered
								<a href="javascript:;" style="vertical-align:middle;" title="FraudLabs Pro Rules triggered."> 
									<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#87888d" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247m2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/></svg>
								</a>
							</p>
							<p style="color:;margin-top:4px; margin-bottom:0;">
								' . (strpos($plan_name, 'Micro') ? '<a href="https://www.fraudlabspro.com/pricing" target="_blank">Upgrade to View »</a>' : $this->escaper->escapeHtml($flpRule)) . '
							</p>
						</td>
					</tr>
					<tr style="vertical-align:top;">
						<td colspan="4">
							<p style="color:#87888d;margin-bottom:0;">Error Message
								<a href="javascript:;" style="vertical-align:middle;" title="FraudLabs Pro error message description."> 
									<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#87888d" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247m2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/></svg>
								</a>
							</p>
							<p style="color:;margin-top:4px; margin-bottom:20px;">' . (($flpErrCode) ? $this->escaper->escapeHtml($flpErrCode) . ': ' : '') . $this->escaper->escapeHtml($flpErrMsg) . '</p>
						</td>
					</tr>
					<tr style="vertical-align:top;">
						<td colspan="4">
							<p style="color:;margin-top:6px; margin-bottom:20px;">For full report, please visit <a href="https://www.fraudlabspro.com/merchant/transaction-details/' . $this->escaper->escapeHtml($data['fraudlabspro_id']) . '" target="_blank">https://www.fraudlabspro.com/merchant/transaction-details/' . $this->escaper->escapeHtml($data['fraudlabspro_id']) . '</a></p>
						</td>
					</tr>
				</table>
				</div>';

		if($data['fraudlabspro_status'] == 'REVIEW'){
			$out .= '
				<div style="text-align: center;margin-top:15px;margin-bottom:15px;">
					<form id="review-action">
						<input type="hidden" name="apiKey" value="' . $data['api_key'] . '" />
						<input type="hidden" name="flpId" value="' . $data['fraudlabspro_id'] . '" />
						<input type="submit" name="flp-approve" style="display: inline-block; color: #55595c;text-align: center;vertical-align: middle; padding:8px 20px;font-size: 15px;line-height: 1.3rem;background-color:#0fb753;color:#ffffff;text-decoration:none;border-radius:4px;margin-bottom:10px;" value="Approve" />&nbsp;
						<input type="submit" name="flp-reject" style="width:92px; display: inline-block; color: #55595c;text-align: center;vertical-align: middle; padding:8px 20px;font-size: 15px;line-height: 1.3rem;background-color:#f1445a;color:#ffffff;text-decoration:none;border-radius:4px;margin-bottom:10px;" value="Reject" />&nbsp;
						<input type="submit" name="flp-reject-blacklist" style="display: inline-block; color: #55595c;text-align: center;vertical-align: middle; padding:8px 20px;font-size: 15px;line-height: 1.3rem;background-color:#3c4349;color:#ffffff;text-decoration:none;border-radius:4px;margin-bottom:10px;" value="Blacklist" />
					</form>
				</div>';
		}

		$out .= '
			</div>';

		return $out;
	}

	private function _post($url, $fields = ''){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($ch, CURLOPT_HTTP_VERSION, '1.1');
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);

		if (!empty($fields)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, (is_array($fields)) ? http_build_query($fields) : $fields);
		}

		$response = curl_exec($ch);

		if (!curl_errno($ch)) {
			return $response;
		}

		return false;
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
}
