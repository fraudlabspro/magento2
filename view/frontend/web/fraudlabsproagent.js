(function () {
    function random(length) {
        var key = '';
        var patterns = '0123456789abcdefghijklmnopqrstuvwxyz';
        var patternsLength = patterns.length;
        for (var i=0; i<length; i++) {
            key += patterns.charAt(Math.floor(Math.random() * patternsLength));
        }
        return key;
    }

    function setCookie(name, value) {
        document.cookie = name + "=" + (value || "") + "; path=/";
    }

    function getCookie(name) {
        var cookies = document.cookie.split(";");
        for(var i=0; i<cookies.length; i++) {
            var cookiePair = cookies[i].split("=");
            if(name == cookiePair[0].trim()) {
                return decodeURIComponent(cookiePair[1]);
            }
        }
        return null;
    }

    var sId = '';
    if (getCookie('ssId') !== null) {
        sId = getCookie('ssId');
    } else {
        sId = random(32);
        setCookie('ssId', sId);
    }

    document.write('<script src="https://cdn.fraudlabspro.com/v3-agent.min.js"></script><script>var agent = new FraudLabsProAgent3("' + sId + '");window.onload = function() {agent.resolve();};</script>');
})();