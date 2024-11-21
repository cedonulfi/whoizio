<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Whois Domain Checker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Whoizio</h1>
        <h2>Fast Whois, IP & Lifecycle Information</h2>
        <form method="POST">
            <input type="text" name="domain" placeholder="Enter domain (e.g., example.com)" required>
            <button type="submit">Check Whois Info</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $apiKey = "GHAGSGA667DS76DSDHGDS655SDGSGDHSH"; // Your API Key
            $domain = trim($_POST['domain']);
            if (!empty($domain)) {
                $apiUrl = "https://api.ip2whois.com/v2?key=" . urlencode($apiKey) . "&domain=" . urlencode($domain);
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    echo "<p class='error'>Error fetching data: " . curl_error($ch) . "</p>";
                } else {
                    $data = json_decode($response, true);
                    if (isset($data['domain'])) {
                        echo "<div class='results'>";
                        echo "<div class='result-item'>";
                        echo "<h3>Domain: {$data['domain']}</h3>";
                        echo "<p><strong>Status:</strong> " . ($data['status'] ?? 'Unknown') . "</p>";
                        echo "<p><strong>Registrar:</strong> " . ($data['registrar']['name'] ?? 'Unknown') . "</p>";
                        echo "<p><strong>Whois Server:</strong> " . ($data['whois_server'] ?? 'Unknown') . "</p>";
                        echo "<p><strong>Create Date:</strong> " . ($data['create_date'] ?? 'Unknown') . "</p>";
                        echo "<p><strong>Update Date:</strong> " . ($data['update_date'] ?? 'Unknown') . "</p>";
                        echo "<p><strong>Expire Date:</strong> " . ($data['expire_date'] ?? 'Unknown') . "</p>";
                        echo "<p><strong>Nameservers:</strong> " . implode(", ", $data['nameservers'] ?? []) . "</p>";

                        function getIP($hostname) {
                            $ip = gethostbyname($hostname);
                            if($ip == $hostname) {
                                return "Could not find IP address for $hostname";
                            } else {
                                return $ip;
                            }
                        }
                        $ip_address = getIP($domain);

                        $url = "https://api.ip2location.io/?key=$apiKey&ip=$ip_address";
                        $chi = curl_init();
                        curl_setopt($chi, CURLOPT_URL, $url);
                        curl_setopt($chi, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($chi, CURLOPT_SSL_VERIFYPEER, false);
                        $responsei = curl_exec($chi);
                        curl_close($chi);
                        // Decode JSON response
                        $datai = json_decode($responsei, true);

                        // Check if the response contains the necessary data
                        if (isset($datai['ip'], $datai['country_code'], $datai['as'])) {
                            $country_code = $datai['country_code'];
                            $asn = $datai['as'];

                            // Format and display the result
                            echo "<p style='color:blue'><strong>IP Address:</strong> $ip_address ($country_code/$asn)</p>";
                        } else {
                            echo "<p style='color:blue'><strong>IP Address:</strong> $ip_address</p>";
                        }

                        // Registrant Information
                        if (!empty($data['registrant'])) {
                            echo "<div class='section-title'>Registrant Info</div>";
                            echo "<p><strong>Name:</strong> " . ($data['registrant']['name'] ?? 'Unknown') . "</p>";
                            echo "<p><strong>Organization:</strong> " . ($data['registrant']['organization'] ?? 'Unknown') . "</p>";
                            echo "<p><strong>Address:</strong> " . ($data['registrant']['street_address'] ?? 'Unknown') . "</p>";
                            echo "<p><strong>City:</strong> " . ($data['registrant']['city'] ?? 'Unknown') . "</p>";
                            echo "<p><strong>Region:</strong> " . ($data['registrant']['region'] ?? 'Unknown') . "</p>";
                            echo "<p><strong>Country:</strong> " . ($data['registrant']['country'] ?? 'Unknown') . "</p>";
                            echo "<p><strong>Email:</strong> " . ($data['registrant']['email'] ?? 'Unknown') . "</p>";
                        }

                        function calculateDomainLifecycleISO($expireDate) {
                            $dates = [];
                            $baseDate = new DateTime($expireDate, new DateTimeZone('UTC'));

                            // Auto-Renew Grace Period (45 days after expiration)
                            $gracePeriodEnd = clone $baseDate;
                            $gracePeriodEnd->modify('+45 days');
                            $dates['Auto-Renew Grace Period Ends'] = $gracePeriodEnd->format('Y-m-d\TH:i:s\Z');

                            // Redemption Period (30 days after grace period)
                            $redemptionPeriodEnd = clone $gracePeriodEnd;
                            $redemptionPeriodEnd->modify('+30 days');
                            $dates['Redemption Period Ends'] = $redemptionPeriodEnd->format('Y-m-d\TH:i:s\Z');

                            // Pending Delete (5 days after redemption period)
                            $pendingDeleteEnd = clone $redemptionPeriodEnd;
                            $pendingDeleteEnd->modify('+5 days');
                            $dates['Pending Delete Ends'] = $pendingDeleteEnd->format('Y-m-d\TH:i:s\Z');

                            // Domain Available (1 day after pending delete)
                            $availableDate = clone $pendingDeleteEnd;
                            $availableDate->modify('+1 day');
                            $dates['Available Again'] = $availableDate->format('Y-m-d\TH:i:s\Z');

                            return $dates;
                        }

                        if (isset($data['expire_date']) && !empty($data['expire_date'])) {
                            $expireDate = $data['expire_date'];

                            // Calculate important dates in a domain cycle (ISO format)
                            $lifecycleDates = calculateDomainLifecycleISO($expireDate);
                            echo "<div class='section-title'>Domain Lifecycle (If Not Renewed)</div>";
                            echo "<p style='color:red'><strong>Expire Date:</strong> $expireDate</p>";
                            foreach ($lifecycleDates as $phase => $date) {
                                echo "<p><strong>$phase:</strong> $date</p>";
                            }
                        }

                        echo "</div>";
                        echo "</div>";
                    } else {
                        echo "<p class='error'>Error: Invalid response for $domain</p>";
                    }
                }
                curl_close($ch);
            } else {
                echo "<p class='error'>Please enter a valid domain.</p>";
            }
        }
        ?>
    </div>
</body>
</html>
