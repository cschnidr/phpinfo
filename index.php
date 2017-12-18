
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <?php
    echo "<h1> Running on POD ".$_ENV['HOSTNAME']." </h1>\n";
   ?>
</head>
<img src="/web/thePub_GreyBlue.png"/>
<!--
Create a persisent volume, mount it to webroot/web and put the referenced image in it!
## webroot for the PHP7 standard container in Openshift: /opt/app-root/src/
## referenced image: wget http://netapp.io/wp-content/uploads/2017/03/thePub_GreyBlue.png
-->
<h1>Change for demo</h1>
</html>


<?php
// phpinfo();
?>
