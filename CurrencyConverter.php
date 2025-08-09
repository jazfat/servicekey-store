<?php
// includes/CurrencyConverter.php

class CurrencyConverter {
    private $pdo;
    private $settings;
    private $cache_file_bcv;
    private $cache_file_cop; // Nuevo archivo de caché para COP
    private $cache_lifetime; // Tiempo de vida del caché (definido en config)

    public function __construct(PDO $pdo, array $site_settings) {
        $this->pdo = $pdo;
        $this->settings = $site_settings;
        $this->cache_file_bcv = __DIR__ . '/bcv_rate.json';
        $this->cache_file_cop = __DIR__ . '/cop_rate.json'; // Define la ruta del caché COP
        $this->cache_lifetime = defined('CURRENCY_CACHE_LIFETIME') ? CURRENCY_CACHE_LIFETIME : 3600; // Usa la constante o un valor por defecto
    }

    /**
     * Obtiene la tasa de cambio para VES con un sistema de respaldo de 3 capas.
     */
    public function getVesRate(): float {
        // 1. Intentar leer del caché local si es reciente
        if (file_exists($this->cache_file_bcv) && (time() - filemtime($this->cache_file_bcv)) < $this->cache_lifetime) {
            $data = json_decode(file_get_contents($this->cache_file_bcv), true);
            if (isset($data['rate']) && $data['rate'] > 0) {
                return (float) $data['rate'];
            }
        }

        // 2. Intentar obtener la tasa en vivo desde la fuente
        $live_rate = $this->fetchBcvUsdRate();
        if ($live_rate !== null && $live_rate > 0) {
            file_put_contents($this->cache_file_bcv, json_encode(['rate' => $live_rate, 'timestamp' => time()]));
            $this->updateSetting('last_known_bcv_rate', $live_rate); // Actualiza la BD con la última tasa exitosa
            return $live_rate;
        }

        // 3. Si la API falla, obtener la última tasa guardada en la base de datos
        return (float) ($this->settings['last_known_bcv_rate'] ?? 37.00);
    }

    /**
     * Obtiene la Tasa Representativa del Mercado (TRM) del dólar en Colombia
     * para la fecha actual. Usa caché, API y respaldo de BD.
     *
     * @return float La tasa de cambio COP/USD.
     */
    public function getCopRate(): float {
        // 1. Intentar leer del caché local si es reciente
        if (file_exists($this->cache_file_cop) && (time() - filemtime($this->cache_file_cop)) < $this->cache_lifetime) {
            $data = json_decode(file_get_contents($this->cache_file_cop), true);
            if (isset($data['rate']) && $data['rate'] > 0) {
                return (float) $data['rate'];
            }
        }

        // 2. Intentar obtener la tasa en vivo desde la API
        $live_rate = $this->fetchColombianTrmRate();
        if ($live_rate !== null && $live_rate > 0) {
            file_put_contents($this->cache_file_cop, json_encode(['rate' => $live_rate, 'timestamp' => time()]));
            $this->updateSetting('cop_exchange_rate', $live_rate); // Actualiza la BD con la última tasa exitosa
            return $live_rate;
        }

        // 3. Si la API falla, obtener la última tasa guardada en la base de datos
        return (float) ($this->settings['cop_exchange_rate'] ?? 4100.00); // Valor por defecto si no hay nada
    }
    
    /**
     * Función principal para convertir y formatear precios.
     */
    public function convertAndFormat(float $price_usd, string $target_currency): string {
        switch ($target_currency) {
            case 'VES':
                $rate_ves = $this->getVesRate();
                $price_in_ves = $price_usd * $rate_ves;
                $price_with_iva = $price_in_ves * 1.16; // Aplicamos 16% de IVA
                return 'Bs. ' . number_format($price_with_iva, 2, ',', '.');
            
            case 'COP':
                $rate_cop = $this->getCopRate();
                $price_in_cop = $price_usd * $rate_cop;
                // La TRM de Colombia ya es un valor entero o con pocos decimales, se suele mostrar sin decimales para valores altos.
                return '$ ' . number_format($price_in_cop, 0, ',', '.') . ' COP';

            case 'USD':
            default:
                return '$ ' . number_format($price_usd, 2, '.', ',') . ' USD';
        }
    }

    // --- Métodos Privados para obtener datos de APIs y actualizar settings ---

    /**
     * Scrapea la tasa USD/VES de BCV.
     */
    private function fetchBcvUsdRate(): ?float {
        $url = "https://www.bcv.org.ve/";
        $xpath_query = "//*[@id='dolar']/div/div/div[2]/strong";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false
        ]);
        $html = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($html === false) {
            custom_log("BCV Rate Fetch Error: " . $error, 'currency_fetch.log'); // Usar custom_log
            return null;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query($xpath_query);

        if ($elements->length > 0) {
            $rawValue = $elements->item(0)->textContent;
            $cleanedValue = str_replace(['.', ','], ['', '.'], $rawValue);
            if (is_numeric($cleanedValue)) {
                custom_log("BCV Rate Fetch Success: " . (float) $cleanedValue, 'currency_fetch.log'); // Usar custom_log
                return (float) $cleanedValue;
            } else {
                custom_log("BCV Rate Fetch Error: Value not numeric: " . $rawValue, 'currency_fetch.log'); // Usar custom_log
                return null;
            }
        }
        custom_log("BCV Rate Fetch Error: Element not found or data missing.", 'currency_fetch.log'); // Usar custom_log
        return null;
    }

    /**
     * Obtiene la Tasa Representativa del Mercado (TRM) del dólar en Colombia
     * para la fecha actual desde la API de datos.gov.co.
     *
     * @return float|null La tasa (float) si tiene éxito, o null si no se encuentra o hay un error.
     */
    private function fetchColombianTrmRate(): ?float {
        $today_date = date('Y-m-d'); // Fecha actual en formato YYYY-MM-DD

        // URL de la API de datos.gov.co para la TRM
        $apiUrl = "https://www.datos.gov.co/resource/32sa-8pi3.json?" .
                  urlencode("\$where") . "=" . urlencode("vigenciadesde <= '${today_date}T00:00:00.000' AND vigenciahasta >= '${today_date}T00:00:00.000'");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        // ADVERTENCIA: Deshabilitar la verificación SSL solo para desarrollo. Habilitar para producción.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $jsonResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($jsonResponse === false || $httpCode !== 200) {
            custom_log("API TRM Colombia Error: Código HTTP: ${httpCode}. Error cURL: ${curlError}", 'currency_fetch.log');
            return null;
        }

        $data = json_decode($jsonResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            custom_log("API TRM Colombia Error: Fallo JSON decode: " . json_last_error_msg(), 'currency_fetch.log');
            return null;
        }

        if (is_array($data) && !empty($data) && isset($data[0]['valor'])) {
            $rawValue = $data[0]['valor'];
            if (is_numeric($rawValue)) {
                custom_log("API TRM Colombia Success: " . (float) $rawValue, 'currency_fetch.log');
                return (float) $rawValue;
            } else {
                custom_log("API TRM Colombia Error: Valor no numérico: " . $rawValue, 'currency_fetch.log');
                return null;
            }
        } else {
            custom_log("API TRM Colombia Error: Datos no encontrados para ${today_date}. Respuesta: " . $jsonResponse, 'currency_fetch.log');
            return null;
        }
    }

    /**
     * Actualiza un ajuste en la tabla de settings de la base de datos.
     * @param string $key La clave del ajuste.
     * @param mixed $value El valor del ajuste.
     */
    private function updateSetting($key, $value) {
        try {
            $stmt = $this->pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } catch (PDOException $e) {
            custom_log("Error updating setting '{$key}': " . $e->getMessage(), 'db_error.log'); // Usar custom_log
        }
    }
}