<?php
/**
 * $Project: GeoGraph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2022 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

//these are the arguments we expect
$param=array('table' => 'visits_by_asn', 'insert' => false);


chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

##################################

/*
create table visits_by_asn (
        `zone` varchar(2) not null,
	`asn` varchar(32) not null,
	`desc` varchar(128) not null,
	`visits` int unsigned not null,
	`period` mediumint unsigned not null,
	`created` timestamp not null default current_timestamp()
);
*/

//$apikey = $CONF['cloudflare_api_token']; //this is the one for R2 only
$apikey = $CONF['cloudflare_api_token_an']; //the analtyics key!

  $zone = 'uk'; $zoneid = $CONF['cloudflare_zone1']; //geograph.org.uk
//$zone = 'ie'; $zoneid = $CONF['cloudflare_zone2']; //geograph.ie

$period = 1800; //30mins

##################################

try {
	//userAgentBrowser is just used, because it seperates out BingBot (and GoogleBot). Which is useful on M$ to seperate Bing from ChatGPT and others)
	//we dont have 'bot score' available to us

    $topAsns = getTopAsnsByTraffic($zoneid, $apikey, 50, $period);

    foreach ($topAsns as $item) {
        echo sprintf(
            "ASN: %d (%s) - Visits: %d (%s)\n",
            $item['dimensions']['clientAsn'],
	    $item['dimensions']['clientASNDescription'],
            $item['sum']['visits'],
            $item['dimensions']['userAgentBrowser']
        );

        if ($param['insert'] && $param['table']) {
		$updates = array();
		$updates['asn'] = $item['dimensions']['clientAsn'];
		$updates['name'] = $item['dimensions']['clientASNDescription'];
		$updates['browser'] = $item['dimensions']['userAgentBrowser'];

		$updates['visits'] = $item['sum']['visits'];
		$updates['period'] = $period;
		$updates['zone'] = $zone;

		$db->Execute($sql = 'INSERT IGNORE INTO '.$param['table'].' SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',
        	 array_values($updates)) or die("$sql\n\n".$db->ErrorMsg()."\n");
	}

    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage()."\n\n";
}

/* TODO, lok for spiking users... 
SELECT asn,name,browser,seen, total, format(total/snapshots,1) as avg,snapshots as cnt, std_dev,avg_weekly as weekly, avg_daily as daily, prev, recent, format(recent / coalesce(avg_daily,100),2)  AS spike FROM asn_view  WHERE (recent / coalesce(avg_daily,100)) > 1.1 and seen > date_sub(now(),interval 1 hour) order by spike desc;
+--------+-------------------------------------------------+---------+---------------------+-------+---------+-----+----------+--------+--------+------+--------+-------+
| asn    | name                                            | browser | seen                | total | avg     | cnt | std_dev  | weekly | daily  | prev | recent | spike |
+--------+-------------------------------------------------+---------+---------------------+-------+---------+-----+----------+--------+--------+------+--------+-------+
| 8151   | UNINET                                          | Chrome  | 2026-05-15 20:11:01 |  1477 | 123.1   |  12 |  14.8125 |   NULL |  117.9 |  150 |    148 | 1.26  |
| 22927  | Telefonica de Argentina                         | Chrome  | 2026-05-15 20:11:01 |  1118 | 93.2    |  12 |  11.8732 |   NULL |   91.8 |   86 |    114 | 1.24  |
| 52468  | UFINET PANAMA S.A.                              | Chrome  | 2026-05-15 20:11:01 |   595 | 49.6    |  12 |   9.7593 |   NULL |   47.6 |   60 |     59 | 1.24  |
| 137526 | Plusnet Inc                                     | Chrome  | 2026-05-15 19:52:01 |    51 | 25.5    |   2 |   2.5000 |   NULL |   23.0 |   23 |     28 | 1.22  |
| 16509  | Amazon.com, Inc.                                | Unknown | 2026-05-15 20:11:01 | 16531 | 1,377.6 |  12 | 299.6939 |   NULL | 1334.9 | 1552 |   1630 | 1.22  |
| 8452   | TE-AS                                           | Chrome  | 2026-05-15 20:11:02 |   334 | 33.4    |  10 |   4.9031 |   NULL |   32.0 |   39 |     39 | 1.22  |
| 13999  | Mega Cable, S.A. de C.V.                        | Chrome  | 2026-05-15 20:11:02 |   574 | 47.8    |  12 |   9.3259 |   NULL |   46.9 |   51 |     54 | 1.15  |
| 17072  | TOTAL PLAY TELECOMUNICACIONES, S.A.P.I. DE C.V. | Chrome  | 2026-05-15 20:11:01 |   741 | 61.8    |  12 |   6.6973 |   NULL |   61.3 |   60 |     68 | 1.11  |
| 24940  | Hetzner Online GmbH                             | Unknown | 2026-05-15 20:11:02 |   258 | 51.6    |   5 |   9.1564 |   NULL |   50.5 |   59 |     56 | 1.11  |
| 3816   | COLOMBIA TELECOMUNICACIONES S.A. ESP BIC        | Chrome  | 2026-05-15 20:11:02 |   363 | 36.3    |  10 |   4.8795 |   NULL |   35.3 |   42 |     39 | 1.10  |
+--------+-------------------------------------------------+---------+---------------------+-------+---------+-----+----------+--------+--------+------+--------+-------+
10 rows in set (0.003 sec)

*/

##################################

/**
 * Fetches the top ASNs by request volume from Cloudflare.
 *
 * @param string $zoneId Cloudflare Zone ID
 * @param string $apiToken Cloudflare API Token (requires Analytics:Read)
 * @param int $limit Number of top ASNs to return
 * @return array List of ASNs with request counts
 */
function getTopAsnsByTraffic($zoneId, $apiToken, $limit = 10, $period = 1800) {
    $endpoint = "https://api.cloudflare.com/client/v4/graphql";

    // Define the time window (last 30 minutes)
    $startTime = gmdate('Y-m-d\TH:i:s\Z', time() - $period);
    $endTime = gmdate('Y-m-d\TH:i:s\Z', time());

//              clientRequestHTTPHost: "www.geograph.org.uk"

    $query = '
    query ($zoneTag: String!, $start: DateTime!, $end: DateTime!, $limit: Int!) {
      viewer {
        zones(filter: { zoneTag: $zoneTag }) {
          httpRequestsAdaptiveGroups(
            limit: $limit
            filter: {
              datetime_geq: $start
              datetime_lt: $end
              originResponseStatus: 200
            }
            orderBy: [sum_visits_DESC]
          ) {
            sum {
              visits
            }
            dimensions {
              clientAsn
	      clientASNDescription
              userAgentBrowser
            }
          }
        }
      }
    }';

    $variables = [
        "zoneTag" => $zoneId,
        "start"   => $startTime,
        "end"     => $endTime,
        "limit"   => $limit
    ];

    $json = json_encode(['query' => $query, 'variables' => $variables]);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiToken",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }

    $result = json_decode($response, true);

    if (isset($result['errors'])) {
        throw new Exception("GraphQL Error: " . json_encode($result['errors']));
    }

    // Navigate to the specific data path
    return $result['data']['viewer']['zones'][0]['httpRequestsAdaptiveGroups'] ?? [];
}

##################################

/*

CREATE OR REPLACE VIEW asn_view AS
SELECT
    asn,
    name,
    browser,

    -- 1. Volume Metrics
    SUM(visits) AS total,
    COUNT(*) AS snapshots,

    -- 2. Steady-State / Flatness Indicators
    STDDEV(visits) AS std_dev,
    -- Coefficient of Variation: Lower means flatter/more automated (e.g., < 0.15 is highly suspect)
    ROUND(STDDEV(visits) / AVG(visits), 4) AS coef_of_variation,

    -- 3. The "Activity Density" (Consistency)
    -- What % of the captured intervals did this ASN actually show up?
    -- If they show up in 100% of intervals, they are a permanent fixture.
    ROUND(COUNT(*) / (SELECT COUNT(DISTINCT created) FROM visits_by_asn), 2) AS activity_ratio,

    -- 4. Baseline Windows (Replaces the rigid < 1 hour filter)
    -- Mid-term baseline: Average visits over the last 24 hours (excluding the current hour to prevent skew)
    ROUND(AVG(IF(created BETWEEN DATE_SUB(NOW(), INTERVAL 25 HOUR)
                             AND DATE_SUB(NOW(), INTERVAL 1 HOUR), visits, NULL)), 1) AS avg_daily,

    -- Long-term baseline: Average visits over the last 7 days
    ROUND(AVG(IF(created BETWEEN DATE_SUB(NOW(), INTERVAL 8 DAY)
                             AND DATE_SUB(NOW(), INTERVAL 25 HOUR), visits, NULL)), 1) AS avg_weekly,

    -- 5. Real-time Status
    -- The absolute most recent snapshot value
    CAST(GROUP_CONCAT(visits ORDER BY created DESC LIMIT 1,1) AS UNSIGNED) AS prev,
    CAST(GROUP_CONCAT(visits ORDER BY created DESC LIMIT 1) AS UNSIGNED) AS recent,
    MAX(created) AS seen

FROM visits_by_asn
WHERE browser IS NOT NULL
GROUP BY asn, browser;
*/
