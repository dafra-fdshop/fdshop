<?php

defined('_JEXEC') or die;

$o = $displayData['order'];
$c = $displayData['config'];
$h = $displayData['helper'];

$groups = [
    1 => [],
    2 => [],
];

foreach (array_merge($displayData['items'], $displayData['bundleItems']) as $item) {
    $group = (int) ($item->packing_group ?? 0);

    if (!$group) {
        $group = $h->currentPackingGroup(
            (int) $item->product_id,
            (int) ($c->document_special_category_id ?? 0)
        );
    }

    $groups[$group === 2 ? 2 : 1][] = $item;
}

$color = $h->safeColor(
    (string) ($displayData['shipment']->shipment_color ?? '')
);

$text = $h->contrastColor($color);

$logo = $h->image(
    (string) ($c->document_company_logo ?? '')
);

?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Packliste</title>

    <style>
        @page {
            margin: 14mm 12mm 14mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            color: #111;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            width: 50%;
            vertical-align: top;
        }

        .header h1 {
            margin: 0 0 7mm;
            font-size: 17pt;
            font-weight: 400;
            white-space: nowrap;
        }

        .company {
            text-align: right;
            line-height: 1.3;
        }

        .company-logo {
            display: block;
            width: 100%;
            margin: 0 0 3mm 0;
            text-align: right;
        }

        .company-logo img {
            display: block;
            width: auto;
            max-width: 45mm;
            max-height: 22mm;
            margin: 0 0 3mm auto;
        }

        .address {
            line-height: 1.4;
        }

        .order-meta {
            margin-top: 4mm;
        }

        .shipping-wrap {
            margin: 4mm 0;
            text-align: right;
        }

        .shipping {
            display: inline-block;
            width: 46%;
            padding: 3mm;
            text-align: left;
            font-size: 9.5pt;
            line-height: 1.45;
        }

        .remark,
        .notes {
            margin: 4mm 0;
            padding: 3mm;
            border: .3mm solid #999;
        }

        .notes {
            height: 15mm;
        }

        .collection {
            page-break-inside: auto;
        }

        .collection h2 {
            margin: 6mm 0 2mm;
            font-size: 13pt;
        }

        /*
         * Dompdf:
         * bewusst KEIN table-layout: fixed
         * und KEIN colgroup.
         *
         * Die Breiten liegen direkt auf TH und TD.
         */
        .items {
            width: 100%;
            border-collapse: collapse;
        }

        .items thead {
            display: table-header-group;
        }

        .items tr {
            page-break-inside: avoid;
        }

        .items th,
        .items td {
            padding: 2mm .5mm;
            border-bottom: .2mm solid #ccc;
            vertical-align: middle;
            font-size: 7.7pt;
        }

        .items .alt {
            background: #eef2f8;
        }

        .items img {
            max-width: 10mm;
            max-height: 10mm;
        }

        .items .image {
            width: 14mm;
            text-align: left;
        }

        .items .sku {
            width: 18mm;
            text-align: left;
        }

        .items .product {
            width: 82mm;
            text-align: left;
            overflow-wrap: normal;
            word-break: normal;
        }

        .items .category {
            width: 24mm;
            text-align: center;
            overflow-wrap: normal;
            word-break: normal;
        }

        .items .quantity {
            width: 12mm;
            text-align: center;
        }

        .items .check {
            width: 18mm;
            text-align: center;
        }

        .box {
            font-family: DejaVu Sans, sans-serif;
            font-size: 16pt;
            line-height: 1;
        }

        .empty {
            padding: 2mm;
            border-bottom: .2mm solid #ccc;
        }
    </style>
</head>

<body>

<table class="header">
    <tr>
        <td>
            <h1>Bestellbestätigung</h1>

            <div class="address">
                <?php if ($o->customer_company) : ?>
                    <?= $h->e($o->customer_company) ?><br>
                <?php endif; ?>

                <?= $h->e(
                    trim($o->customer_first_name . ' ' . $o->customer_last_name)
                ) ?><br>

                <?= $h->e($o->customer_street) ?><br>

                <?= $h->e(
                    $h->addressLine(
                        (string) $o->customer_postal_code,
                        (string) $o->customer_city
                    )
                ) ?>
            </div>

            <div class="order-meta">
                Bestellnummer:
                <?= $h->e($o->order_number) ?><br>

                Bestelldatum:
                <?= $h->date($o->created) ?>
            </div>
        </td>

        <td>
            <div class="company">
                <div class="company-logo">
                    <?= $logo ?>
                </div>

                <b>
                    <?= $h->e(
                        $c->document_company_name ?? 'FDShop'
                    ) ?>
                </b><br>

                <?= $h->e(
                    $c->document_company_street ?? ''
                ) ?><br>

                <?= $h->e(
                    $h->addressLine(
                        (string) ($c->document_company_postal_code ?? ''),
                        (string) ($c->document_company_city ?? '')
                    )
                ) ?>
            </div>
        </td>
    </tr>
</table>

<div class="shipping-wrap">
    <div
        class="shipping"
        style="background:<?= $color ?>;color:<?= $text ?>"
    >
        <b>Versandinformationen:</b><br>

        Berechtigung/Schein: <?= $h->permitSnapshot((int)($o->buyer_group_id ?? 0)) ? 'Ja' : 'Nein' ?><br>

        Abholstation
        „<?= $h->e($o->shipment_name) ?>“<br>

        <span class="box">□</span>
        Gepackt: Paketanzahl __________
    </div>
</div>

<div class="remark">
    <b>Bemerkung Käufer:</b>

    <?php if (trim((string) $o->order_note) !== '') : ?>
        <?= nl2br($h->e($o->order_note)) ?>
    <?php else : ?>
        Keine Bemerkung
    <?php endif; ?>
</div>

<div class="notes">
    <b>Notizen:</b>
</div>

<?php foreach ([1, 2] as $group) : ?>

    <?php
    $title = $group === 1
        ? ($c->document_collection_one_title ?? 'Sammlung 1')
        : ($c->document_collection_two_title ?? 'Sammlung 2');
    ?>

    <section class="collection">

        <h2><?= $h->e($title) ?></h2>

        <?php if ($groups[$group]) : ?>

            <table class="items">

                <thead>
                    <tr>
                        <th class="image">Bild</th>
                        <th class="sku">Art.-Nr.</th>
                        <th class="product">Produkt</th>
                        <th class="category">Kategorie</th>
                        <th class="quantity">Anzahl</th>
                        <th class="check">Gerichtet</th>
                        <th class="check">Gepackt</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($groups[$group] as $n => $item) : ?>

                    <tr<?= $n % 2 ? ' class="alt"' : '' ?>>

                        <td class="image">
                            <?= $h->image(
                                (string) ($item->document_image_path ?? ''),
                                36
                            ) ?>
                        </td>

                        <td class="sku">
                            <?= $h->e($item->sku) ?>
                        </td>

                        <td class="product">
                            <?php if(!empty($item->bundle_name)):?><span style="font-size:6.8pt;color:#555"><?php echo $h->e($item->bundle_name); ?>:</span><br><?php endif;?><?= $h->e($item->product_name) ?>
                        </td>

                        <td class="category">
                            <?= $group === 2
                                ? 'Verbundfeuerwerk'
                                : 'keine Verbünde' ?>
                        </td>

                        <td class="quantity">
                            <?= $h->qty($item->quantity) ?>
                        </td>

                        <td class="check">
                            <span class="box">□</span>
                        </td>

                        <td class="check">
                            <span class="box">□</span>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php else : ?>

            <p class="empty">
                Keine Positionen
            </p>

        <?php endif; ?>

    </section>

<?php endforeach; ?>

</body>
</html>
