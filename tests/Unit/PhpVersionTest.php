<?php

it('runs on php 8.3 or newer', function () {
    expect(PHP_VERSION_ID)->toBeGreaterThanOrEqual(80300);
});
