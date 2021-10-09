<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function validateMultiLanguages($request, $rules, $multiLanguagesRules, array $messages = [], array $customAttributes = []): array
    {
        $languages = config('laravellocalization.supportedLocales');
        foreach ($languages as $short => $language) {
            foreach ($multiLanguagesRules as $key => $value) {
                $rules["{$short}.{$key}"] = $value;
            }
        }
        try {
            return $this->validate($request, $rules);
        } catch (ValidationException $e) {
            throw $e;
        }
    }


}
