<?php

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

final class PackagingService
{
    public const UNIT_TYPES = ['Stück', 'Display', 'Schinken', 'VE'];
    public const DISCOUNT_TYPES = ['none', 'percent', 'amount'];

    public function normalize(array $data): array
    {
        $type = trim((string) ($data['unit_type'] ?? 'Stück'));
        $data['unit_type'] = in_array($type, self::UNIT_TYPES, true) ? $type : 'Stück';
        $data['unit_quantity'] = (int) ($data['unit_quantity'] ?? 1);
        $discountType = trim((string) ($data['unit_discount_type'] ?? 'none'));
        $data['unit_discount_type'] = in_array($discountType, self::DISCOUNT_TYPES, true) ? $discountType : 'none';
        $data['unit_discount_value'] = (float) ($data['unit_discount_value'] ?? 0);

        if ($data['unit_type'] === 'Stück') {
            $data['unit_quantity'] = 1;
            $data['unit_discount_type'] = 'none';
            $data['unit_discount_value'] = 0.0;
            return $data;
        }
        if ($data['unit_quantity'] < 2) {
            throw new \InvalidArgumentException('Eine Verpackungseinheit muss mindestens 2 Stück enthalten.');
        }
        if ($data['unit_discount_type'] === 'percent' && ($data['unit_discount_value'] < 0 || $data['unit_discount_value'] >= 100)) {
            throw new \InvalidArgumentException('Der prozentuale Verpackungsrabatt muss zwischen 0 und unter 100 liegen.');
        }
        if ($data['unit_discount_type'] === 'amount' && $data['unit_discount_value'] <= 0) {
            throw new \InvalidArgumentException('Der feste Verpackungspreis muss größer als 0 sein.');
        }
        if ($data['unit_discount_type'] === 'none') {
            $data['unit_discount_value'] = 0.0;
        }
        return $data;
    }

    public function calculate(object|array $product): array
    {
        $row = is_object($product) ? get_object_vars($product) : $product;
        $validConfiguration = true;
        try {
            $row = $this->normalize($row);
        } catch (\InvalidArgumentException) {
            $validConfiguration = false;
            $row['unit_type'] = in_array((string) ($row['unit_type'] ?? ''), self::UNIT_TYPES, true) ? (string) $row['unit_type'] : 'Stück';
            $row['unit_quantity'] = max(1, (int) ($row['unit_quantity'] ?? 1));
            $row['unit_discount_type'] = 'none';
            $row['unit_discount_value'] = 0.0;
        }
        $piece = $this->money((int) ($row['discount_active'] ?? 0) === 1 && (float) ($row['discount_price'] ?? 0) > 0
            ? (float) $row['discount_price'] : (float) ($row['sale_price'] ?? 0));
        $regular = $this->money($piece * $row['unit_quantity']);
        $package = match ($row['unit_discount_type']) {
            'percent' => $this->money($regular * (1 - $row['unit_discount_value'] / 100)),
            'amount' => $this->money($row['unit_discount_value']),
            default => $regular,
        };
        return [
            'valid' => $validConfiguration && $row['unit_type'] !== 'Stück', 'unit_type' => $row['unit_type'],
            'unit_quantity' => $row['unit_quantity'], 'unit_discount_type' => $row['unit_discount_type'],
            'unit_discount_value' => $row['unit_discount_value'], 'piece_price' => $piece,
            'regular_price' => $regular, 'price' => $package, 'saving' => $this->money(max(0, $regular - $package)),
        ];
    }

    private function money(float $value): float { return round($value, 2); }
}
