#!/usr/bin/env php
<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/tools/media-migration/import.php';
[$db, $service] = bootstrapJoomla();
$skus = ['FD1300','FD1313','FD1392','FD1442','FD1493'];
$cleanup = in_array('--cleanup', $argv, true);
foreach ($skus as $sku) {
    $db->setQuery($db->getQuery(true)->select('p.id')->from($db->quoteName('#__fdshop_products','p'))
        ->innerJoin($db->quoteName('#__fdshop_products_details','d').' ON d.product_id=p.id')->where('d.sku='.$db->quote($sku)));
    $id = (int) $db->loadResult();
    if ($cleanup) {
        if ($id > 0) {
            $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__fdshop_media'))->where('product_id=' . $id));
            foreach (array_map('intval', $db->loadColumn()) as $mediaId) {
                $service->deleteProductImage($id, $mediaId);
            }
            foreach (['#__fdshop_product_category_map','#__fdshop_products_details','#__fdshop_products'] as $table) {
                $column = $table === '#__fdshop_products' ? 'id' : 'product_id';
                $db->setQuery($db->getQuery(true)->delete($db->quoteName($table))->where($db->quoteName($column).'='.$id))->execute();
            }
        }
        continue;
    }
    if ($id > 0) {
        $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__fdshop_product_category_map'))->where('product_id='.$id)->where('category_id=900010'));
        if ((int) $db->loadResult() === 0) {
            $db->setQuery($db->getQuery(true)->insert($db->quoteName('#__fdshop_product_category_map'))->columns(['product_id','category_id','is_primary'])->values("$id,900010,1"))->execute();
        }
        $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__fdshop_product_category_map'))->where('product_id='.$id)->where('category_id=900010'));
        echo "$sku EXISTS $id category900010=" . (int) $db->loadResult() . "\n"; continue;
    }
    $columns=['product_name','alias','currency','min_order_qty','max_order_qty','step_order_qty','is_active','meta_title','in_stock'];
    $values=[$db->quote('Media Migration '.$sku),$db->quote('media-migration-'.strtolower($sku)),$db->quote('EUR'),'1','10','1','1',$db->quote(''),$db->quote('Verfügbar')];
    $db->setQuery($db->getQuery(true)->insert($db->quoteName('#__fdshop_products'))->columns(array_map([$db,'quoteName'],$columns))->values(implode(',',$values)))->execute();
    $id=(int)$db->insertid();
    $columns=['product_id','sku','gtin','stock_quantity','low_stock','created','created_by'];
    $values=[$id,$db->quote($sku),$db->quote(''),'10','2',$db->quote(gmdate('Y-m-d H:i:s')),'0'];
    $db->setQuery($db->getQuery(true)->insert($db->quoteName('#__fdshop_products_details'))->columns(array_map([$db,'quoteName'],$columns))->values(implode(',',$values)))->execute();
    $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__fdshop_categories'))->where('is_active=1')->order('id ASC'),0,1);
    $category=(int)$db->loadResult();
    if ($category > 0) { $db->setQuery($db->getQuery(true)->insert($db->quoteName('#__fdshop_product_category_map'))->columns(['product_id','category_id','is_primary'])->values("$id,$category,1"))->execute(); }
    echo "$sku CREATED $id\n";
}
echo $cleanup ? "Test products cleaned.\n" : "Test products prepared.\n";
