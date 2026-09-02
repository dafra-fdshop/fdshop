# FDShop fixture base

This directory contains deterministic, artificial data only for the local
Compose project `fdshop`. Direct SQL creates a starting state; it does not
prove any FDShop business workflow.

Run through `scripts/fdshop fixtures`, `fixtures-verify`, or `test-reset`.
The loader deletes only FDShop domain data, imports `base.sql`, restores the
synthetic image, and verifies the complete state. IDs in the `900000+` range
are internal relation anchors. Browser tests must use stable SKUs, aliases,
coupon codes, bundle numbers, order numbers, and names instead.

## Coupon model scope

Fixtures represent schema `0.0.19` and the current CouponService only:
`percent`/`fixed`, minimum total, validity dates, usage limits, and simple
user/buyer-group/product/category mappings.

The documented target fields `maximum_discount_amount`, `coupon_type`, and
mapping modes such as `allow`/`exclude` do not exist technically and are not
simulated. The technical names `valid_to` and `usage_limit_per_user` are used
because their behavior is directly implemented by CouponService.

The two order records are historical snapshots for later administrator-view
tests. They do not claim that checkout or operational order creation exists.
