<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParserReviewLog extends Model
{
    protected $fillable = [
        'incoming_report_email_id',
        'report_template_id',
        'user_id',
        'action',
        'scope',
        'parser_type',
        'parser_configuration',
    ];

    protected function casts(): array
    {
        return [
            'parser_configuration' => 'array',
        ];
    }
}
