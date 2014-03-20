<html>
    <style>
        body { font-size:12px; font-family:arial; padding:0; margin:0; background: #F4F4F4; }
        #hathoora_redirect { background: #fff; border: 1px solid #ccc; margin:0 auto; width: 80%; margin-top:40px; color:#888; padding:10px;}
        #hathoora_redirect a { text-decoration: none; color:#333; }
        #hathoora_redirect p.why { font-size:10px; }
        #hathoora_redirect pre { font-family: monospace; text-align: left; margin:5px 20px; background: #F4F4F4; border:1px dotted #ccc; padding:10px; }
    </style>
</html>
<body>
    <div id="hathoora_redirect">
        <h1>
            Redirct URL: <a href="<?php echo $redirectURL; ?>"><?php echo $redirectURL; ?></a>
        </h1>

        <?php
            if (is_array($flashMessage))
            {
                echo '
                <p>Flash Message:</p>
                <pre>'. print_r($flashMessage, true) .'</pre>';
            }
        ?>

        <p class="why">
            Redirect has been intercepted because of because of <i>hathoora.logger.webprofiler.show_redirects</i>
        </p>
    </div>
</body>