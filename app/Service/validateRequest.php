<?php

namespace App\Service;

use Illuminate\Support\Facades\Validator;

class validateRequest
{
    public function validate(array $data, array $rules, array $messages = [])
    {
        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            return (new Response())->ApiResponse([
                'status' => 422,
                'message' => '',
                    'items' => $validator->errors(),
            ]);
        }

        return true;
    }
}
