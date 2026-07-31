<?php

it('adds and removes po detail rows', function () {
    $c = Livewire\Livewire::test('po-details', [
        'rows'    => [],
        'options' => [1 => 'Product A'],
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
    $c = Livewire\Livewire::test('po-details', [
        'rows'    => [],
        'options' => [1 => 'Product A', 2 => 'Product B'],
    ]);

    $c->set('rows.0.po_detail_id_product', 1);
    $c->call('addRow');
    $c->set('rows.1.po_detail_id_product', 1);

    expect($c->get('rows'))->toHaveCount(2);
    expect($c->get('rows.1.po_detail_id_product'))->toBe('');
    $c->assertHasErrors('rows.1.po_detail_id_product');

    expect($c->instance()->takenBy(1))->toBe(['1']);
});
