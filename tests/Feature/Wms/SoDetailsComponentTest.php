<?php

it('adds and removes so detail rows', function () {
    $c = Livewire\Livewire::test('so-details', [
        'rows'    => [],
        'options' => [1 => 'Product A'],
        'prices'  => [1 => 1500],
    ]);

    expect($c->get('rows'))->toHaveCount(1);

    $c->call('addRow');
    expect($c->get('rows'))->toHaveCount(2);

    $c->call('removeRow', 0);
    expect($c->get('rows'))->toHaveCount(1);

    // never drops below one row
    $c->call('removeRow', 0);
    expect($c->get('rows'))->toHaveCount(1);
});

it('rejects duplicate product', function () {
    $c = Livewire\Livewire::test('so-details', [
        'rows'    => [],
        'options' => [1 => 'Product A', 2 => 'Product B'],
        'prices'  => [1 => 1500, 2 => 2000],
    ]);

    $c->set('rows.0.so_detail_id_product', 1);
    $c->call('addRow');
    $c->set('rows.1.so_detail_id_product', 1);

    expect($c->get('rows'))->toHaveCount(2);
    expect($c->get('rows.1.so_detail_id_product'))->toBe('');
    $c->assertHasErrors('rows.1.so_detail_id_product');
});

it('totals qty times product price', function () {
    $c = Livewire\Livewire::test('so-details', [
        'rows'    => [],
        'options' => [1 => 'Product A', 2 => 'Product B'],
        'prices'  => [1 => 1500, 2 => 2000],
    ]);

    $c->set('rows.0.so_detail_id_product', 1);
    $c->set('rows.0.so_detail_qty', 3);
    $c->call('addRow');
    $c->set('rows.1.so_detail_id_product', 2);
    $c->set('rows.1.so_detail_qty', 2);

    expect($c->instance()->priceOf(0))->toBe(1500.0);
    expect($c->instance()->total)->toBe(3 * 1500.0 + 2 * 2000.0);
});
