<?php

namespace FDShop\Component\FDShop\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class BundleService implements BundleServiceInterface
{
    public function __construct(private readonly DatabaseInterface $db) {}

    public function getBundlesForProduct(int $productId): array
    {
        $query = $this->db->getQuery(true)->select(['b.id', 'b.bundle_number', 'b.bundle_name', 'b.description', 'b.image_path'])
            ->from($this->db->quoteName('#__fdshop_bundles', 'b'))
            ->innerJoin($this->db->quoteName('#__fdshop_bundle_items', 'bi') . ' ON bi.bundle_id = b.id')
            ->where('bi.product_id = ' . (int) $productId)->where('b.is_active = 1')->order('b.bundle_name ASC');
        $this->db->setQuery($query);
        return (array) $this->db->loadObjectList();
    }

    public function getBuilder(int $bundleId, int $userId = 0, int $savedBundleId = 0, int $cartBundleId = 0, string $sessionId = ''): array
    {
        $bundle = $this->loadBundle($bundleId);
        $products = $this->loadProducts($bundleId);
        $rules = $this->loadRules($bundleId);
        $selection = [];
        if ($savedBundleId > 0) {
            if ($userId < 1) throw new \DomainException('Gespeicherte Bundles stehen nur angemeldeten Kunden zur Verfügung.');
            $this->assertSavedOwner($userId, $savedBundleId, $bundleId);
            $selection = $this->loadSelection('#__fdshop_saved_bundle_items', 'saved_bundle_id', $savedBundleId);
        } elseif ($cartBundleId > 0) {
            $this->assertCartOwner($userId, $sessionId, $cartBundleId, $bundleId);
            $selection = $this->loadSelection('#__fdshop_cart_bundle_items', 'cart_bundle_id', $cartBundleId);
        }
        return ['bundle' => $bundle, 'products' => $products, 'rules' => $rules, 'selection' => $selection, 'saved' => $userId > 0 ? $this->loadSaved($userId, $bundleId) : [], 'is_authenticated' => $userId > 0];
    }

    public function calculate(int $bundleId, array $items, int $userId = 0, string $sessionId = '', int $excludeCartBundleId = 0): array
    {
        $bundle = $this->loadBundle($bundleId);
        $products = [];
        foreach ($this->loadProducts($bundleId) as $product) $products[(int) $product->id] = $product;
        $normal = [];
        foreach ($items as $key => $value) {
            $productId = is_array($value) ? (int) ($value['product_id'] ?? $key) : (int) $key;
            $quantity = is_array($value) ? (int) ($value['quantity'] ?? 0) : (int) $value;
            if ($quantity <= 0) continue;
            if (!isset($products[$productId])) throw new \DomainException('Ein gewähltes Produkt gehört nicht zu diesem Bundle.');
            if ($quantity > (int) $bundle->max_quantity_per_product) throw new \DomainException('Die maximale Anzahl je Produkt wurde überschritten.');
            $normal[$productId] = $quantity;
        }
        if (count($normal) < 2) throw new \DomainException('Bitte wählen Sie mindestens zwei verschiedene Produkte.');
        $totalQuantity = array_sum($normal);
        $rule = null;
        foreach ($this->loadRules($bundleId) as $candidate) if ((int) $candidate->min_quantity <= $totalQuantity) $rule = $candidate;
        $percent = (float) ($rule->discount_percent ?? 0);
        $subtotalGross = 0.0; $subtotalNet = 0.0; $lines = [];
        foreach ($normal as $productId => $quantity) {
            $p = $products[$productId];
            $available = max(0, (float) $p->stock_quantity - (float) $p->reserved_quantity);
            $demand = $this->normalCartDemand($userId, $sessionId, $productId) + $this->cartDemand($userId, $sessionId, $productId, $excludeCartBundleId) + $quantity;
            if ($demand > $available + 0.0001) throw new \DomainException('Die gewünschte Bundle-Menge für ' . $p->product_name . ' ist nicht verfügbar.');
            $gross = (float) $p->effective_price; $tax = (float) $p->tax_rate;
            $lineGross = round($gross * $quantity, 2); $lineNet = round($lineGross / (1 + $tax / 100), 2);
            $subtotalGross += $lineGross; $subtotalNet += $lineNet;
            $lines[] = ['product_id' => $productId, 'product_name' => (string) $p->product_name, 'sku' => (string) $p->sku, 'image_path' => (string) $p->image_path, 'quantity' => $quantity, 'regular_price_gross' => (float) $p->sale_price, 'effective_unit_price_gross' => $gross, 'tax_rate' => $tax, 'base_gross' => $lineGross, 'base_net' => $lineNet, 'currency' => (string) $p->currency];
        }
        $subtotalGross = round($subtotalGross, 2); $subtotalNet = round($subtotalNet, 2);
        $discountGross = round($subtotalGross * $percent / 100, 2); $discountNet = round($subtotalNet * $percent / 100, 2);
        $allocatedGross = 0.0; $allocatedNet = 0.0; $last = count($lines) - 1;
        foreach ($lines as $index => &$line) {
            $share = $subtotalGross > 0 ? $line['base_gross'] / $subtotalGross : 0;
            $lineDiscountGross = $index === $last ? round($discountGross - $allocatedGross, 2) : round($discountGross * $share, 2);
            $lineDiscountNet = $index === $last ? round($discountNet - $allocatedNet, 2) : round($discountNet * $share, 2);
            $allocatedGross += $lineDiscountGross; $allocatedNet += $lineDiscountNet;
            $line['bundle_discount_gross'] = $lineDiscountGross;
            $line['line_total_gross'] = round($line['base_gross'] - $lineDiscountGross, 2);
            $line['line_total_net'] = round($line['base_net'] - $lineDiscountNet, 2);
            $line['unit_price_gross'] = round($line['line_total_gross'] / $line['quantity'], 4);
            $line['unit_price_net'] = round($line['line_total_net'] / $line['quantity'], 4);
            unset($line['base_gross'], $line['base_net']);
        }
        unset($line);
        return ['bundle' => $bundle, 'lines' => $lines, 'distinct_product_count' => count($lines), 'total_quantity' => $totalQuantity, 'subtotal_net' => $subtotalNet, 'subtotal_gross' => $subtotalGross, 'discount_percent' => $percent, 'discount_amount_net' => $discountNet, 'discount_amount_gross' => $discountGross, 'total_net' => round($subtotalNet - $discountNet, 2), 'total_gross' => round($subtotalGross - $discountGross, 2), 'currency' => (string) ($lines[0]['currency'] ?? 'EUR')];
    }

    public function save(int $userId, int $bundleId, string $name, array $items, int $savedBundleId = 0): int
    {
        if ($userId < 1) throw new \DomainException('Bitte melden Sie sich an, um das Bundle zu speichern.');
        $calculation = $this->calculate($bundleId, $items, $userId);
        $name = trim($name) ?: (string) $calculation['bundle']->bundle_name;
        $now = Factory::getDate()->toSql();
        $this->db->transactionStart();
        try {
            if ($savedBundleId > 0) {
                $this->assertSavedOwner($userId, $savedBundleId, $bundleId);
                $query = $this->db->getQuery(true)->update($this->db->quoteName('#__fdshop_saved_bundles'))->set('saved_name = ' . $this->db->quote($name))->set('modified = ' . $this->db->quote($now))->where('id = ' . $savedBundleId);
                $this->db->setQuery($query)->execute();
            } else {
                $row = (object) ['id' => 0, 'user_id' => $userId, 'bundle_id' => $bundleId, 'saved_name' => $name, 'created' => $now];
                $this->db->insertObject('#__fdshop_saved_bundles', $row, 'id'); $savedBundleId = (int) $row->id;
            }
            $this->replaceItems('#__fdshop_saved_bundle_items', 'saved_bundle_id', $savedBundleId, $calculation['lines']);
            $this->db->transactionCommit(); return $savedBundleId;
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function deleteSaved(int $userId, int $savedBundleId): void
    {
        $this->assertSavedOwner($userId, $savedBundleId);
        $this->db->transactionStart();
        try { $this->deleteChildren('#__fdshop_saved_bundle_items', 'saved_bundle_id', $savedBundleId); $this->deleteRow('#__fdshop_saved_bundles', $savedBundleId); $this->db->transactionCommit(); } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function addToCart(int $userId, string $sessionId, int $bundleId, array $items, int $cartBundleId = 0): int
    {
        $this->assertOwner($userId, $sessionId);
        $calculation = $this->calculate($bundleId, $items, $userId, $sessionId, $cartBundleId);
        $b = $calculation['bundle']; $now = Factory::getDate()->toSql();
        $this->db->transactionStart();
        try {
            if ($cartBundleId > 0) { $this->assertCartOwner($userId, $sessionId, $cartBundleId, $bundleId); $this->deleteChildren('#__fdshop_cart_bundle_items', 'cart_bundle_id', $cartBundleId); $this->deleteRow('#__fdshop_cart_bundles', $cartBundleId); }
            $row = (object) ['id' => 0, 'user_id' => $userId, 'session_id' => $userId > 0 ? '' : $sessionId, 'bundle_id' => $bundleId, 'bundle_number' => $b->bundle_number, 'bundle_name' => $b->bundle_name, 'image_path' => $b->image_path, 'currency' => $calculation['currency'], 'distinct_product_count' => $calculation['distinct_product_count'], 'total_quantity' => $calculation['total_quantity'], 'subtotal_net' => $calculation['subtotal_net'], 'subtotal_gross' => $calculation['subtotal_gross'], 'discount_percent' => $calculation['discount_percent'], 'discount_amount_net' => $calculation['discount_amount_net'], 'discount_amount_gross' => $calculation['discount_amount_gross'], 'total_net' => $calculation['total_net'], 'total_gross' => $calculation['total_gross'], 'created' => $now];
            $this->db->insertObject('#__fdshop_cart_bundles', $row, 'id'); $cartBundleId = (int) $row->id;
            foreach ($calculation['lines'] as $ordering => $line) { unset($line['image_path']); $line['cart_bundle_id'] = $cartBundleId; $line['ordering'] = $ordering + 1; $itemRow = (object) $line; $this->db->insertObject('#__fdshop_cart_bundle_items', $itemRow); }
            $this->db->transactionCommit(); return $cartBundleId;
        } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function removeFromCart(int $userId, string $sessionId, int $cartBundleId): void
    {
        $this->assertCartOwner($userId, $sessionId, $cartBundleId);
        $this->db->transactionStart();
        try { $this->deleteChildren('#__fdshop_cart_bundle_items', 'cart_bundle_id', $cartBundleId); $this->deleteRow('#__fdshop_cart_bundles', $cartBundleId); $this->db->transactionCommit(); } catch (\Throwable $e) { $this->db->transactionRollback(); throw $e; }
    }

    public function loadCartBundles(int $userId, string $sessionId): array
    {
        $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_cart_bundles'))->where($this->ownerWhere($userId, $sessionId))->order('created ASC, id ASC');
        $this->db->setQuery($query); $bundles = (array) $this->db->loadObjectList();
        foreach ($bundles as $bundle) { $q = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_cart_bundle_items'))->where('cart_bundle_id = ' . (int) $bundle->id)->order('ordering ASC, id ASC'); $this->db->setQuery($q); $bundle->items = (array) $this->db->loadObjectList(); }
        return $bundles;
    }

    public function cartDemand(int $userId, string $sessionId, int $productId, int $excludeCartBundleId = 0): float
    {
        $query = $this->db->getQuery(true)->select('COALESCE(SUM(i.quantity), 0)')->from($this->db->quoteName('#__fdshop_cart_bundle_items', 'i'))->innerJoin($this->db->quoteName('#__fdshop_cart_bundles', 'b') . ' ON b.id = i.cart_bundle_id')->where($this->ownerWhere($userId, $sessionId, 'b'))->where('i.product_id = ' . $productId);
        if ($excludeCartBundleId > 0) $query->where('b.id <> ' . $excludeCartBundleId);
        $this->db->setQuery($query); return (float) $this->db->loadResult();
    }

    private function loadBundle(int $id): object { $q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_bundles'))->where('id = '.(int)$id)->where('is_active = 1'); $this->db->setQuery($q); $b=$this->db->loadObject(); if(!$b) throw new \DomainException('Das Bundle ist nicht verfügbar.'); return $b; }
    private function loadProducts(int $id): array { $price='CASE WHEN p.discount_active=1 AND p.discount_price>0 THEN p.discount_price ELSE p.sale_price END'; $mediaTable=$this->db->quoteName('#__fdshop_media'); $q=$this->db->getQuery(true)->select(['p.id','p.product_name','p.sale_price','p.currency','d.sku','d.stock_quantity','d.reserved_quantity',$price.' AS effective_price','COALESCE(pp.tax_rate,c.general_vat_rate,0) AS tax_rate','COALESCE(m.path_small,m.path_standard,m.path_mobile,\'\') AS image_path'])->from($this->db->quoteName('#__fdshop_bundle_items','bi'))->innerJoin($this->db->quoteName('#__fdshop_products','p').' ON p.id=bi.product_id')->innerJoin($this->db->quoteName('#__fdshop_products_details','d').' ON d.product_id=p.id')->leftJoin($this->db->quoteName('#__fdshop_product_prices','pp').' ON pp.product_id=p.id')->leftJoin($this->db->quoteName('#__fdshop_config','c').' ON c.id=1')->leftJoin($this->db->quoteName('#__fdshop_media','m')." ON m.id=(SELECT m2.id FROM {$mediaTable} m2 WHERE m2.product_id=p.id AND m2.media_type='image' ORDER BY m2.is_primary DESC,m2.ordering ASC,m2.id ASC LIMIT 1)")->where('bi.bundle_id='.(int)$id)->where('p.ribbon_bundle=1')->where('p.is_active=1')->where('p.is_deleted=0')->order('bi.ordering ASC,bi.id ASC'); $this->db->setQuery($q); return (array)$this->db->loadObjectList(); }
    private function loadRules(int $id): array { $q=$this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__fdshop_bundle_discount_rules'))->where('bundle_id='.(int)$id)->order('min_quantity ASC, ordering ASC'); $this->db->setQuery($q); return (array)$this->db->loadObjectList(); }
    private function loadSelection(string $table,string $column,int $id): array { $q=$this->db->getQuery(true)->select(['product_id','quantity'])->from($this->db->quoteName($table))->where($this->db->quoteName($column).'='.(int)$id)->order('ordering ASC,id ASC'); $this->db->setQuery($q); return array_map(static fn($r)=>['product_id'=>(int)$r->product_id,'quantity'=>(int)$r->quantity],(array)$this->db->loadObjectList()); }
    private function loadSaved(int $userId,int $bundleId): array { $q=$this->db->getQuery(true)->select(['id','saved_name','modified','created'])->from($this->db->quoteName('#__fdshop_saved_bundles'))->where('user_id='.(int)$userId)->where('bundle_id='.(int)$bundleId)->order('modified DESC,created DESC'); $this->db->setQuery($q); return (array)$this->db->loadObjectList(); }
    private function replaceItems(string $table,string $column,int $id,array $lines): void { $this->deleteChildren($table,$column,$id); foreach($lines as $ordering=>$line){ $row=(object)[$column=>$id,'product_id'=>(int)$line['product_id'],'quantity'=>(int)$line['quantity'],'ordering'=>$ordering+1]; $this->db->insertObject($table,$row); } }
    private function deleteChildren(string $table,string $column,int $id): void { $q=$this->db->getQuery(true)->delete($this->db->quoteName($table))->where($this->db->quoteName($column).'='.(int)$id); $this->db->setQuery($q)->execute(); }
    private function deleteRow(string $table,int $id): void { $q=$this->db->getQuery(true)->delete($this->db->quoteName($table))->where('id='.(int)$id); $this->db->setQuery($q)->execute(); }
    private function assertSavedOwner(int $userId,int $id,int $bundleId=0): void { $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_saved_bundles'))->where('id='.(int)$id)->where('user_id='.(int)$userId); if($bundleId>0)$q->where('bundle_id='.(int)$bundleId); $this->db->setQuery($q); if(!(int)$this->db->loadResult())throw new \DomainException('Das gespeicherte Bundle wurde nicht gefunden.'); }
    private function assertCartOwner(int $userId,string $sessionId,int $id,int $bundleId=0): void { $q=$this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__fdshop_cart_bundles'))->where('id='.(int)$id)->where($this->ownerWhere($userId,$sessionId)); if($bundleId>0)$q->where('bundle_id='.(int)$bundleId); $this->db->setQuery($q); if(!(int)$this->db->loadResult())throw new \DomainException('Das Warenkorb-Bundle wurde nicht gefunden.'); }
    private function normalCartDemand(int $userId,string $sessionId,int $productId): float { $q=$this->db->getQuery(true)->select('COALESCE(SUM(quantity*unit_quantity_snapshot),0)')->from($this->db->quoteName('#__fdshop_cart'))->where($this->ownerWhere($userId,$sessionId))->where('product_id='.(int)$productId); $this->db->setQuery($q); return (float)$this->db->loadResult(); }
    private function assertOwner(int $userId,string $sessionId): void { if($userId<1 && trim($sessionId)==='')throw new \DomainException('Die Warenkorb-Sitzung ist ungültig.'); }
    private function ownerWhere(int $userId,string $sessionId,string $alias=''): string { $prefix=$alias!==''?$alias.'.':''; return $userId>0?$prefix.'user_id='.(int)$userId:'('.$prefix.'user_id=0 AND '.$prefix.'session_id='.$this->db->quote($sessionId).')'; }
}
