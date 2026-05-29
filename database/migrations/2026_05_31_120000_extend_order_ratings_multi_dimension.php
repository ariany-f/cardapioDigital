<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_ratings', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->foreignId('motoboy_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('delivery_rating')->nullable()->after('comment');
            $table->text('delivery_comment')->nullable()->after('delivery_rating');
            $table->unsignedTinyInteger('restaurant_rating')->nullable()->after('delivery_comment');
            $table->text('restaurant_comment')->nullable()->after('restaurant_rating');
            $table->string('status', 20)->default('approved')->after('restaurant_comment');
            $table->timestamp('moderated_at')->nullable()->after('status');
            $table->foreignId('moderated_by_user_id')->nullable()->after('moderated_at')->constrained('users')->nullOnDelete();
        });

        $ratings = DB::table('order_ratings')->select('id', 'order_id', 'rating', 'restaurant_rating', 'status')->get();

        foreach ($ratings as $rating) {
            $order = DB::table('orders')->where('id', $rating->order_id)->first();
            $delivery = DB::table('deliveries')->where('order_id', $rating->order_id)->first();

            DB::table('order_ratings')->where('id', $rating->id)->update([
                'branch_id' => $order?->branch_id,
                'motoboy_id' => $delivery?->motoboy_id,
                'restaurant_rating' => $rating->restaurant_rating ?? $rating->rating,
                'status' => $rating->status ?: 'approved',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('order_ratings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by_user_id');
            $table->dropColumn([
                'branch_id',
                'motoboy_id',
                'delivery_rating',
                'delivery_comment',
                'restaurant_rating',
                'restaurant_comment',
                'status',
                'moderated_at',
            ]);
        });
    }
};
