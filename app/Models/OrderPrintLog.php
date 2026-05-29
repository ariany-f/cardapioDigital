<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPrintLog extends Model
{
    protected $fillable = ['order_id', 'format', 'ticket_type', 'printed_by'];
}
