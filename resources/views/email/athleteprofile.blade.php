<!DOCTYPE html>
<html>

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta name="email-subject" content="Your Event Pictures Are Ready!">

    <title>Your Event Pictures Are Ready! - {{ config('app.name') }}</title>
    <style type="text/css">
        a:hover {
            text-decoration: underline !important;
        }
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
    <!-- 100% body table -->
    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8"
        style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;">
        <tbody>
            <tr>
                <td>
                    <table style="background-color: #f2f3f8; max-width:670px; margin:0 auto;" width="100%"
                        border="0" align="center" cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td style="height:80px;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="text-align:center;">
                                    <a href="{{ url('/') }}" title="{{ config('app.name') }}" target="_blank">
                                        <img width="250" src="{{ url('frontend/assets/logo1-1.png') }}"
                                            title="{{ config('app.name') }}" alt="{{ config('app.name') }}">
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td style="height:20px;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td>
                                    <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                                        style="max-width:670px; background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                                        <tbody>
                                            <tr>
                                                <td style="height:40px;">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:0 35px;">
                                                    <h1
                                                        style="color:#1e1e2d; font-weight:500; margin:0;font-size:32px;font-family:'Rubik',sans-serif;">
                                                        Hi {{ $profile['name'] }},</h1>
                                                    <p
                                                        style="font-size:15px; color:#455056; margin:8px 0 0; line-height:24px;">
                                                        Thank you for participating in our event. We have captured some amazing pictures of you!</p>
                                                    <p
                                                        style="font-size:15px; color:#455056; margin:8px 0 0; line-height:24px;">
                                                        You can view and download your pictures by signing up or logging in to your account. You also have the option to purchase high-quality images directly from our platform.</p>
                                                    <a href="{{ url('/athlete-profile', $profile['unique_string']) }}"
                                                        style="background:rgb(0, 0, 0);text-decoration:none !important;display:inline-block;font-weight:500;margin-top:24px;color:#fff;text-transform:uppercase;font-size:14px;padding:10px 24px;display:inline-block;border-radius:50px;">Sign
                                                        In/Sign Up & View Your Images</a>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="height:40px;">&nbsp;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="height:20px;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="text-align:center;">
                                    <p
                                        style="font-size:14px; color:rgba(69, 80, 86, 0.7411764705882353); line-height:18px; margin:0 0 0;">
                                        © {{ config('app.name') }} <a target="_blank" href="{{ url('/') }}"
                                            style="color: #7b7b7b;"><strong>{{ config('app.name') }}</strong></a> </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="height:80px;">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <!--/100% body table-->
</body>

</html>
