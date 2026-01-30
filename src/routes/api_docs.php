<?php

use Dedoc\Scramble\Scramble;

if(app()->environment('local')){
    Scramble::registerUiRoute('/docs/api');
    Scramble::registerJsonSpecificationRoute('/docs/api.json');
}else{
    Route::get('/docs/api', function () {
        return view('scramble::docs', [
            'spec' => file_get_contents(public_path('api.json')),
            'config' => Scramble::getGeneratorConfig('default'),
        ]);
    });
    Route::get('/docs/api.json', function () {
        return response()->json(json_decode(file_get_contents(public_path('api.json'))));
    });
}
