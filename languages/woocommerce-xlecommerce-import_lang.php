<?php
define('TEXT_CRON_SETUP_HEADING','Set up cron jobs through cPanel using this procedure:');
define('TEXT_CRON_SETUP_INSTRUCTION',"<p>1. Log on to your cPanel Interface. <br>2. Go to <b>Advanced</b> section. <br>3. Click on <b>Cron Jobs</b>.<br>4. Select the specific time from the lists provided.<br>5. You should enter the command to run in the <b>Command</b> field. Following are the 2 files:<br>Product feed (recommended once a day): woocommerce-xlecommerce-import_cron.php<br>Inventory Feed (recommended every 15 mins: woocommerce-xlecommerce-import-inventory_cron.php</p>");
define('CRON_SCHEDULE_TEXT','*Updates Products once a day & Inventory once per fifteen minutes');
?>