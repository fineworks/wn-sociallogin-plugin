<?php namespace Flynsarmy\SocialLogin\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableUpdateUserPhone extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            $table->string('phone', 100)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('users', function($table)
        {
            $table->dropColumn('phone');
        });
    }
}
