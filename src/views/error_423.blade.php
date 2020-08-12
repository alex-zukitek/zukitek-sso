<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }} | APPLICATION UNAVAILABLE</title>
    <style>
        body { padding: 0; margin: 0; } * { -webkit-box-sizing: border-box; box-sizing: border-box; } #notfound { position: relative; height: 100vh; background-color: #02aeb5; } #notfound .notfound { position: absolute; left: 50%; top: 50%; -webkit-transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); transform: translate(-50%, -50%); } .notfound { max-width: 460px; width: 100%; text-align: center; line-height: 1.4; } .notfound .notfound-404 { height: 158px; line-height: 153px; } .notfound .notfound-404 h1 { font-family: 'Josefin Sans', sans-serif; color: #222222; font-size: 220px; letter-spacing: 10px; margin: 0px; font-weight: 700; text-shadow: 2px 2px 0px #c9c9c9, -2px -2px 0px #c9c9c9; } .notfound .notfound-404 h1>span { text-shadow: 2px 2px 0px #e3665d, -2px -2px 0px #c9443a, 0px 0px 8px #b51d12; } .notfound p { font-family: 'Josefin Sans', sans-serif; color: #c9c9c9; font-size: 16px; font-weight: 400; margin-top: 10px; margin-bottom: 30px; } .notfound a { font-family: 'Josefin Sans', sans-serif; font-size: 14px; text-decoration: none; text-transform: uppercase; background: transparent; color: #c9c9c9; border: 2px solid #c9c9c9; display: inline-block; padding: 10px 25px; font-weight: 700; -webkit-transition: 0.2s all; transition: 0.2s all; } .notfound a:hover { color: #b51d12; border-color: #b51d12; } @media only screen and (max-width: 480px) { .notfound .notfound-404 { height: 122px; line-height: 122px; } .notfound .notfound-404 h1 { font-size: 122px; } }
    </style>
</head>
<body>
<div id="notfound">
    <div class="notfound">
        <div class="notfound-404">
            <h1>4<span>2</span>3</h1>
        </div>
        <p>{{ request()->get('errorMessage') ? request()->get('errorMessage') : 'THIS SERVICE UNAVAILABLE FOR YOUR ACCOUNT' }}</p>
        <a href="{{ config('sso.sso_server_url') }}">ACCOUNT MANAGEMENT</a>
    </div>
</div>
</body>
</html>
