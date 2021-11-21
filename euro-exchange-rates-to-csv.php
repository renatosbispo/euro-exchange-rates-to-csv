<?php

function log_error($error_message) {
    $timestamp = "[" . date("H:i:s d-m-Y") . "]";
    $error_log_filename = "errors.log";
    $error_log_file = fopen($error_log_filename, "a+");
    fwrite($error_log_file, "$timestamp $error_message" . PHP_EOL);
    fclose($error_log_file);
    echo "ERROR: $error_message" . PHP_EOL;
}

function get_xml_from_url($url) {
    // Setup curl
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);

    // Execute curl
    $curl_result = curl_exec($ch);

    // Handle error
    $curl_error = curl_error($ch);

    if($curl_error) {
        log_error($curl_error);
    }

    curl_close($ch);

    return simplexml_load_string($curl_result);
}

function extract_euro_exchange_rates_from_xml($xml) {
    $euro_exchange_rates = [];

    foreach($xml->Cube as $euro_exchange_rate_xml) {
        $euro_exchange_rate_xml_attributes = $euro_exchange_rate_xml->attributes();
        $currency = $euro_exchange_rate_xml_attributes->currency;
        $rate = $euro_exchange_rate_xml_attributes->rate;
        $euro_exchange_rates[] = ["$currency", "$rate"];
    }

    return $euro_exchange_rates;
}

function save_to_csv($headers, $data, $filename) {
    $file_to_save_to = fopen("$filename.csv", "w");

    fputcsv($file_to_save_to, $headers);

    foreach($data as $row) {
        fputcsv($file_to_save_to, $row);
    }

    fclose($file_to_save_to);
}

function main() {
    $euro_exchange_rates_url = "https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";
    $output_dir = "out";

    // Retrieve XML file
    $xml = get_xml_from_url($euro_exchange_rates_url);

    if (!$xml) {
        log_error("Failed to load XML from string.");
        return;
    }

    $xml_data_root = $xml->Cube->Cube;

    // Extract the date from the XML file
    $date = $xml_data_root->attributes()->time;

    // Extract the euro exchange rates data from the XML file
    $euro_exchange_rates = extract_euro_exchange_rates_from_xml($xml_data_root);

    // Save the exchange rates to a CSV file
    $csv_headers = ["Currency", "Rate"];
    save_to_csv($csv_headers, $euro_exchange_rates, "$output_dir/euro-exchange-rates-$date");
    echo "CSV file saved to output folder." . PHP_EOL;
}

main();
