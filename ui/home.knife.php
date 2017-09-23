<!DOCTYPE html>
<html lang="en-us" dir="ltr">
    <head>
        <meta charset="utf-8">
        <title>Alit PHP</title>
        <style>
            body{
                font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
                font-weight:lighter;
                font-size:1rem;
                line-height:0.5;
                color:#BFBFBF;
                background-color:#fff;
            }
            a{
                color:#BFBFBF!important;
                text-decoration:none;
            }
            a:hover{
                text-decoration:underline;
            }
            .title{
                padding-top: 150px;
                font-weight:lighter;
                font-size:50px;
            }
        </style>
    </head>
    <body>
        <center>
    <div style="padding:30px">
        <h1 class="title">{{ $fw }}</h1>
        <p><a href="{{ $link }}">{{ $tagline }}</a></p>
    </div>
</center>
</body>
</html>
