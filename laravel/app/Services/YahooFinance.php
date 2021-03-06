<?php
namespace App\Services;

class YahooFinance
{
    const BASE_URL = "http://query.yahooapis.com/v1/public/yql";

    /**
     * Parameters and column arrays to be fetched
     */
    private $format = array();

    public function __construct()
    {
        $this->addFormat('s', 'symbol');
        $this->addFormat('n', 'name');
        $this->addFormat('l1', 'price');
        $this->addFormat('d1', 'date');
        $this->addFormat('t1', 'time');
        $this->addFormat('c', 'change');
        $this->addFormat('o', 'open');
        $this->addFormat('p', 'close');
        $this->addFormat('h', 'high');
        $this->addFormat('g', 'low');
        $this->addFormat('v', 'volume');
    }

    /**
     * Add parameters and column to be fetched
     *
     * List of parameters that can be fetched from Yahoo:
     * a – Ask
     * a2 – Average Daily Volume
     * a5 – Ask Size
     * b – Bid
     * b2 – Ask (Real-time)
     * b3 – Bid (Real-time)
     * b4 – Book Value
     * b6 – Bid Size
     * c – Change and Percent Change
     * c1 – Change
     * c3 – Commission
     * c6 – Change (Real-time)
     * c8 – After Hours Change (Real-time)
     * d – Dividend/Share
     * d1 – Last Trade Date
     * d2 – Trade Date
     * e – Earnings/Share
     * e1 – Error Indication (returned for symbol changed / invalid)
     * e7 – EPS Est. Current Year
     * e8 – EPS Est. Next Year
     * e9 – EPS Est. Next Quarter
     * f6 – Float Shares
     * g – Day’s Low
     * g1 – Holdings Gain Percent
     * g3 – Annualized Gain
     * g4 – Holdings Gain
     * g5 – Holdings Gain Percent (Real-time)
     * g6 – Holdings Gain (Real-time)
     * h – Day’s High
     * i – More Info
     * i5 – Order Book (Real-time)
     * j – 52-week Low
     * j1 – Market Capitalization
     * j3 – Market Cap (Real-time)
     * j4 – EBITDA
     * j5 – Change from 52 Week Low
     * j6 – Percent Change from 52 Week Low
     * k – 52-week High
     * k1 – Last Trade (Real-time) with Time
     * k2 – Change Percent (Real-time)
     * k3 – Last Trade Size
     * k4 – Change from 52 Week High
     * k5 – Percent Change from 52 Week High
     * l – Last Trade (with time)
     * l1 – Last Trade (without time)
     * l2 – High Limit
     * l3 – Low Limit
     * m – Day’s Range
     * m2 – Day’s Range (Real-time)
     * m3 – 50 Day Moving Average
     * m4 – 200 Day Moving Average
     * m5 – Change from 200 Day Moving Average
     * m6 – Percent Change from 200 Day Moving Average
     * m7 – Change from 50 Day Moving Average
     * m8 – Percent Change from 50 Day Moving Average
     * n – Name
     * n4 – Notes
     * o – Open
     * p – Previous Close
     * p1 – Price Paid
     * p2 – Change in Percent
     * p5 – Price/Sales
     * p6 – Price/Book
     * q – Ex-Dividend Date
     * r – P/E Ratio
     * r1 – Dividend Pay Date
     * r2 – P/E (Real-time)
     * r5 – PEG Ratio
     * r6 – Price/EPS Est. Current Year
     * r7 – Price/EPS Est. Next Year
     * s – Symbol
     * s1 – Shares Owned
     * s7 – Short Ratio
     * t1 – Last Trade Time
     * t6 – Trade Links
     * t7 – Ticker Trend
     * t8 – 1 Year Target Price
     * v – Volume
     * v1 – Holdings Value
     * v7 – Holdings Value (Real-time)
     * w – 52 Week Range
     * w1 – Day’s Value Change
     * w4 – Day’s Value Change (Real-time)
     * x – Stock Exchange
     * y – Dividend Yield
     *
     * @param string $format Parameter/Format to be fetched
     * @param string $format Column name
     * @return void
     */
    public function addFormat($format, $column)
    {
        $this->format[] = [$format, $column];
    }

    /**
     * Populate parameters/format to be fetched
     *
     * @param array $array Parameters/Formats and Columns to be fetched
     * @return void
     */
    public function setFormat($array)
    {
        unset($this->format);
        $this->format[] = $array;
    }

    /**
     * Remove parameters/format.
     *
     * @return void
     */
    public function removeFormat()
    {
        unset($this->format);
        $this->format = array();
    }

    /**
     * Get Stock Data
     *
     * @param string $symbol
     * @return object
     */
    public function getQuotes($symbol)
    {
        $paramsString = "";
        $columnsString = "";

        foreach ($this->format as $format) {
            $paramsString .= $format[0];

            if ($format !== end($this->format)) {
                $columnsString .= $format[1] . ",";
            } else {
                $columnsString .= $format[1];
            }
        }

        $result = [];

        if ($paramsString != "" && $columnsString != "") {
            $yql_query = "select * from csv where url='http://download.finance.yahoo.com/d/quotes.csv?s=$symbol&f=$paramsString&e=.csv' and columns='$columnsString'";
            $yql_query_url = self::BASE_URL . "?q=" . urlencode($yql_query) . "&format=json";
            // Make call with cURL
            $session = curl_init($yql_query_url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            $json = curl_exec($session);
            // Convert JSON to PHP object
            $result = json_decode($json);

            return $result->query->results->row;
        }
        return $result;
    }

    /**
     * Get Stock Data
     *
     * @param string $symbol
     * @param int $begin
     * @param int $end
     * @return object
     */
    public function getHistoryQuote($symbol, $begin = 30, $end = 0)
    {
        $begin = Date('Y-m-d', strtotime("-{$begin} days"));
        $end = Date('Y-m-d', strtotime("-{$end} days"));

        $yql_query = "select * from yahoo.finance.historicaldata where symbol = '$symbol' and startDate = '$begin' and endDate = '$end'";
        $yql_query_url = self::BASE_URL . "?q=" . urlencode($yql_query) . "&diagnostics=true&env=store://datatables.org/alltableswithkeys&format=json&callback=";
        // Make call with cURL
        $session = curl_init($yql_query_url);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($session);
        // Convert JSON to PHP object
        $result = json_decode($json);

        return $result->query->results->quote;
    }
}