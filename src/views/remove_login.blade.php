<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }}</title>
    <script>
        const _x_ = '{{ config('sso.sso_server_url') }}';
        function _o_(e,t,s){let i=new Date;i.setTime(i.getTime()-(1000*s*60*24));let n="expires="+i.toUTCString();document.cookie=e+"="+t+";"+n+";path=/"}function _d_m_(e){let t=[];if(e.origin!==_x_)return!1;t=JSON.parse(e.data);for(let e=0;e<t.length;e++)_o_(t[e].code,t[e].value,t[e].seconds)}window.addEventListener?window.addEventListener("message",_d_m_,!1):window.attachEvent("onmessage",_d_m_);
        {{--
        const ssoURL = '{{ config('sso.sso_server_url') }}';
        function setCookie(cname, cvalue, seconds) {
            let d = new Date();
            d.setTime(d.getTime() - (1000*seconds*60*24));
            let expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }
        function handleSave(evt) {
            let data = [];
            if (evt.origin !== ssoURL) {
                return false;
            } else {
                data = JSON.parse(evt.data);
            }
            for (let i = 0; i < data.length; i++) {
                setCookie(data[i].code, data[i].value, data[i].seconds);
            }
        }
        if (window.addEventListener) {
            window.addEventListener('message', handleSave, false);
        } else {
            window.attachEvent('onmessage', handleSave);
        }
        --}}
    </script>
</head>

<body>
REMOVED
</body>
</html>
