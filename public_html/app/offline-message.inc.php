<?

if (!empty($CONF['submission_message'])) {

        //display the temporary message, that notes going to be maintaince soon.
        print "<div id=\"submission_message\">";
        print $CONF['submission_message'];
        print "</div>";

        //if have a time that will be offline, will need to reload this page, because its left open in peristant iframe
        if (!empty($CONF['critical'])) { // will contain something like '1400'; for 2pm
                $critical_hour = substr($CONF['critical'], 0, 2);
                $critical_min  = substr($CONF['critical'], 2, 2);

                $date = new DateTime('today', new DateTimeZone('Europe/London')); //athough server should already be using this timezone!
                $date->setTime($critical_hour, $critical_min, 0);

                ?><script>
		    var maintenanceStartUTC = <?php echo (int)$date->getTimestamp(); ?>;
		    var isInitialLoad = true; // Flag to track the first run
		    var buttonTxt = null;

		    async function checkSystemStatus() {
		        try {
		            const response = await fetch('/app/status.json.php', { cache: 'no-store' });

		            if (response.ok) {
		                // SERVER IS BACK ONLINE
		                const msgBox = document.getElementById('submission_message');
		                if (msgBox) {
		                    // Hide the message or update it to say "Good to go"
		                    msgBox.style.display = 'none';
		                }
				const btn = document.getElementById('submit_button');
				if (btn) {
					btn.disabled = false;
					if (buttonTxt)  btn.textContent = buttonTxt;
				}
		            } else if (response.status === 503) {
		                // STILL IN MAINTENANCE
		                const data = await response.json();
		                updateMaintenanceUI(data);
		            }
		        } catch (error) {
		            // Server might be totally down (Gateway Error / DNS)
		            console.error("Connection lost or server offline");
		        }
		    }

		    function updateMaintenanceUI(data) {
		        const msgBox = document.getElementById('submission_message');
		        if (!msgBox) return;

		        let content = data.message;

		        // If we have a timestamp, check if we've passed it
		        if (data.maintenanceStartUTC) {
		            const now = Math.floor(Date.now() / 1000);
		            if (now >= data.maintenanceStartUTC) {
		                content = "<strong>Maintenance has started.</strong> Data saved locally; submission will resume shortly.";
				const btn = document.getElementById('submit_button');
				if (btn) {
					btn.disabled = true;
					if (!buttonTxt) buttonTxt = btn.textContent;
					btn.textContent = 'Server Offline - please wait';
				}
		            }
		        }

		        msgBox.innerHTML = content;
		        msgBox.style.display = 'block';
		    }

		    // Trigger check when iframe becomes visible
		    const subObserver = new IntersectionObserver((entries) => {
		        if (entries[0].isIntersecting) {

		              // If it's the first time the observer runs, just flip the flag
		                if (isInitialLoad) {
		                    isInitialLoad = false;
		                    return;
		                }

		            checkSystemStatus();
		        }
		    });
		    subObserver.observe(document.body);

		</script>
        <? }
}


