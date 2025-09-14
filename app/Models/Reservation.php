<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'start', // startカラムを許可
        'end',   // endカラムを許可
    ];

    /**
     * ネイティブなタイプへキャストする属性
     * ★★ startとendをdatetimeオブジェクトとして扱うように設定 ★★
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    /**
     * この予約を所有するユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


