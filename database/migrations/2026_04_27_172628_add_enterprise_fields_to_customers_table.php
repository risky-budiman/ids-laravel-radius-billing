<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Infrastructure
            $table->string('odc_id')->nullable()->after('longitude');
            $table->string('odp_id')->nullable()->after('odc_id');
            $table->integer('odp_port')->nullable()->after('odp_id');
            $table->integer('cable_length')->nullable()->after('odp_port');
            $table->integer('vlan_id')->nullable()->after('cable_length');
            $table->string('static_ip')->nullable()->after('vlan_id');
            
            // KYC & Photos
            $table->string('customer_type')->default('personal')->after('status'); // personal, corporate, vip
            $table->string('identity_photo')->nullable()->after('ktp');
            $table->string('house_photo')->nullable()->after('identity_photo');
            $table->string('cpe_photo')->nullable()->after('house_photo');
            
            // CPE Details
            $table->string('cpe_brand')->nullable()->after('cpe_photo');
            $table->string('cpe_model')->nullable()->after('cpe_brand');
            $table->string('cpe_mac')->nullable()->after('cpe_model');
            
            // Description
            $table->text('description')->nullable()->after('cpe_mac');

            // Foreign Keys
            $table->foreign('odc_id')->references('id')->on('odcs')->onDelete('set null');
            $table->foreign('odp_id')->references('id')->on('odps')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['odc_id']);
            $table->dropForeign(['odp_id']);
            
            $table->dropColumn([
                'odc_id', 'odp_id', 'odp_port', 'cable_length', 'vlan_id', 'static_ip',
                'customer_type', 'identity_photo', 'house_photo', 'cpe_photo',
                'cpe_brand', 'cpe_model', 'cpe_mac', 'description'
            ]);
        });
    }
};
