<?php


use YlsIdeas\CockroachDb\Processor\CockroachDbProcessor;

test('process column listing', function () {
    $processor = new CockroachDbProcessor();

    $listing = [['column_name' => 'id'], ['column_name' => 'name'], ['column_name' => 'email']];
    $expected = ['id', 'name', 'email'];

    expect($processor->processColumnListing($listing))->toEqual($expected);

    // convert listing to objects to simulate PDO::FETCH_CLASS
    foreach ($listing as &$row) {
        $row = (object) $row;
    }

    expect($processor->processColumnListing($listing))->toEqual($expected);
});
