<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->string('name');
            $table->integer('position')->default(0);
            $table->unsignedInteger('wip')->nullable();
            $table->timestamps();
        });

        Schema::create('kanban_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('stage_id')->nullable();
            $table->foreignId('assignee_id')->nullable();
            $table->string('status')->default('todo');
            $table->integer('sort')->nullable();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('priority')->nullable();
            $table->date('due_on')->nullable();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->timestamps();
        });
    }
};
