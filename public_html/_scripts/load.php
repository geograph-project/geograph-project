<?

$load = $load2 = $load3 = '';

if (!isset($_ENV["OS"]) || strpos($_ENV["OS"],'Windows') === FALSE) {
                $buffer = "0 0 0";
                if (is_readable("/proc/loadavg")) {
                        $f = fopen("/proc/loadavg","r");
                        if ($f)
                        {
                                if (!feof($f)) {
                                        $buffer = fgets($f, 1024);
                                }
                                fclose($f);
                        }
                }
                $loads = explode(" ",$buffer);
                $load=(float)$loads[0];
}


        if (function_exists('sys_getloadavg'))
                $load2 = array_shift(sys_getloadavg());


	if (!empty($_SERVER['CONF_PROFILE']))
		$load3 = `cat /sys/fs/cgroup/cpu/tasks | wc -l`;


print date('r ').trim(`hostname`)." $load $load2 $load3\n";

