<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;

final class CurrencyCatalog
{
    /** @var array<string,array{code:string,name:string,countries:list<string>,minor_units:int|null}>|null */
    private ?array $currencies = null;

    /** @return array<string,array{code:string,name:string,countries:list<string>,minor_units:int|null}> */
    public function all(): array
    {
        if ($this->currencies !== null) {
            return $this->currencies;
        }

        $path = dirname(__DIR__, 2).'/resources/data/iso-4217-list-one.xml';
        $xml = simplexml_load_file($path);
        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('The ISO 4217 currency catalogue could not be loaded.');
        }

        $grouped = [];
        foreach ($xml->CcyTbl->CcyNtry as $entry) {
            $code = strtoupper(trim((string) $entry->Ccy));
            if ($code === '') {
                continue;
            }
            $country = $this->title((string) $entry->CtryNm);
            $minorUnits = trim((string) $entry->CcyMnrUnts);
            $grouped[$code] ??= ['code' => $code, 'name' => trim((string) $entry->CcyNm), 'countries' => [], 'minor_units' => ctype_digit($minorUnits) ? (int) $minorUnits : null];
            if ($country !== '' && ! in_array($country, $grouped[$code]['countries'], true)) {
                $grouped[$code]['countries'][] = $country;
            }
        }
        ksort($grouped);

        return $this->currencies = $grouped;
    }

    /** @return list<string> */
    public function codes(): array
    {
        return array_keys($this->all());
    }

    /** @return list<array{value:string,code:string,label:string,meta:string,search:string}> */
    public function selectOptions(): array
    {
        return array_values(array_map(static function (array $currency): array {
            $countries = implode(', ', $currency['countries']);

            return [
                'value' => $currency['code'],
                'code' => $currency['code'],
                'label' => $currency['name'],
                'meta' => $countries,
                'search' => implode(' ', [$currency['code'], $currency['name'], $countries]),
            ];
        }, $this->all()));
    }

    private function title(string $value): string
    {
        return mb_convert_case(mb_strtolower(trim($value), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
