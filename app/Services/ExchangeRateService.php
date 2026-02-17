<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const API_URL = 'https://api.exchangerate-api.com/v4/latest/';
    private const CACHE_TTL = 86400; // 24 hours (was 3600 = 1 hour)
    private const BASE_CURRENCY = 'IDR';

    private static array $runtimeCache = [];

    /**
     * Get current exchange rate from one currency to another
     * 
     * @param string $from Source currency code (e.g., 'USD')
     * @param string $to Target currency code (e.g., 'IDR')
     * @return float|null Exchange rate or null if failed
     */
    public function getCurrentRate(string $from, string $to): ?float
    {
        // If same currency, rate is 1
        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$from}_{$to}";

        // Request-level memory cache to avoid multiple DB/Cache lookups in one request
        if (isset(self::$runtimeCache[$cacheKey])) {
            return self::$runtimeCache[$cacheKey];
        }

        return self::$runtimeCache[$cacheKey] = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to) {
            try {
                /** @var \Illuminate\Http\Client\Response $response */
                $response = Http::timeout(5)->get(self::API_URL . $from);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['rates'][$to])) {
                        return (float) $data['rates'][$to];
                    }
                }

                Log::warning("Failed to fetch exchange rate from {$from} to {$to}");
                return null;
            } catch (\Exception $e) {
                Log::error("Exchange rate API error: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Convert amount from one currency to another
     * 
     * @param float $amount Amount to convert
     * @param string $from Source currency
     * @param string $to Target currency
     * @param float|null $rate Optional manual rate (overrides API)
     * @return float Converted amount
     */
    public function convertAmount(float $amount, string $from, string $to, ?float $rate = null): float
    {
        if ($from === $to) {
            return $amount;
        }

        $exchangeRate = $rate ?? $this->getCurrentRate($from, $to);

        if ($exchangeRate === null) {
            // Fallback: return original amount if conversion fails
            return $amount;
        }

        return $amount * $exchangeRate;
    }

    /**
     * Convert any currency amount to base currency (IDR)
     * 
     * @param float $amount Amount to convert
     * @param string $currency Source currency
     * @param float|null $rate Optional manual rate
     * @return float Amount in IDR
     */
    public function toBaseCurrency(float $amount, string $currency, ?float $rate = null): float
    {
        return $this->convertAmount($amount, $currency, self::BASE_CURRENCY, $rate);
    }

    /**
     * Get formatted rate display
     * 
     * @param string $from Source currency
     * @param string $to Target currency
     * @return string Formatted rate string (e.g., "1 USD = 15,750 IDR")
     */
    public function getFormattedRate(string $from, string $to): string
    {
        $rate = $this->getCurrentRate($from, $to);

        if ($rate === null) {
            return "Rate unavailable";
        }

        return "1 {$from} = " . number_format($rate, 2) . " {$to}";
    }
}
