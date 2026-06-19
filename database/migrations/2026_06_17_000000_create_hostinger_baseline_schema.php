<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema alinhado ao banco de produção (Hostinger / phpMyAdmin dump).
 * Usa hasTable() para ser seguro em bancos já existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usuario')) {
            Schema::create('usuario', function (Blueprint $table) {
                $table->string('username', 120)->primary();
                $table->char('pass_salt', 64);
                $table->char('pass_hash', 64);
                $table->integer('pass_iterations')->default(200000);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('op_tasks')) {
            Schema::create('op_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('taskCode', 32);
                $table->string('titulo', 500);
                $table->string('setor', 180)->default('');
                $table->string('regiao', 64)->default('');
                $table->string('responsavel', 120);
                $table->string('clientesAfetados', 32)->default('');
                $table->string('coordenadas', 120)->default('');
                $table->string('localizacao_texto', 512)->default('');
                $table->mediumText('descricao')->nullable();
                $table->string('categoria', 48);
                $table->date('prazo')->nullable();
                $table->string('prioridade', 24)->default('Média');
                $table->string('status', 48)->default('Criada');
                $table->boolean('is_parent_task')->default(false);
                $table->unsignedBigInteger('parent_task_id')->nullable();
                $table->string('criadaEm', 64)->default('');
                $table->longText('historico')->nullable();
                $table->unsignedInteger('active_duration_minutes')->nullable();
                $table->string('chat_thread_key', 140)->default('');
                $table->string('nome_cliente', 255)->default('');
                $table->string('protocolo', 180)->default('');
                $table->string('ordem_servico', 180)->default('');
                $table->string('numero_os', 180)->default('');
                $table->string('sub_processo', 120)->default('');
                $table->string('data_entrada', 64)->default('');
                $table->string('data_instalacao', 64)->default('');
                $table->string('assinada_por', 120)->default('');
                $table->string('assinada_em', 64)->default('');
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('categoria');
                $table->index('status');
                $table->index('parent_task_id');
                $table->index('updated_at');
                $table->index(['categoria', 'status']);
                $table->index(['categoria', 'regiao']);
                $table->index(['status', 'prazo']);
                $table->index('taskCode');
                $table->index(['parent_task_id', 'status']);
                $table->index('active_duration_minutes');
            });
        }

        if (! Schema::hasTable('os_tecnicos')) {
            Schema::create('os_tecnicos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('task_id');
                $table->unsignedBigInteger('parent_task_id')->nullable();
                $table->string('tecnico_nome', 100);
                $table->string('ordem_servico', 180)->default('');
                $table->string('titulo', 500)->default('');
                $table->string('task_code', 32)->default('');
                $table->string('categoria', 48)->default('');
                $table->string('regiao', 64)->default('');
                $table->string('status', 48)->default('');
                $table->string('protocolo', 180)->default('');
                $table->string('prioridade', 24)->default('');
                $table->date('data_criacao')->nullable();
                $table->string('data_conclusao', 64)->default('');
                $table->string('criada_em', 64)->default('');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('task_id');
                $table->foreign('task_id')->references('id')->on('op_tasks')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('app_config')) {
            Schema::create('app_config', function (Blueprint $table) {
                $table->string('cfg_key', 64)->primary();
                $table->longText('cfg_value')->nullable();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (! Schema::hasTable('app_activity_event')) {
            Schema::create('app_activity_event', function (Blueprint $table) {
                $table->id();
                $table->string('username', 120);
                $table->string('event_type', 48);
                $table->string('severity', 16)->default('info');
                $table->string('message', 600)->default('');
                $table->string('ref_type', 32)->default('');
                $table->unsignedBigInteger('ref_id')->nullable();
                $table->string('op_category', 48)->default('');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index(['username', 'created_at']);
                $table->index('updated_at');
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('app_notification')) {
            Schema::create('app_notification', function (Blueprint $table) {
                $table->id();
                $table->string('kind', 48)->default('task_added');
                $table->string('title', 255)->default('');
                $table->string('message', 600)->default('');
                $table->string('ref_type', 32)->default('');
                $table->unsignedBigInteger('ref_id')->nullable();
                $table->string('op_category', 48)->default('');
                $table->string('created_by', 120)->default('');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('created_at');
                $table->index('updated_at');
                $table->index('kind');
            });
        }

        if (! Schema::hasTable('op_task_image')) {
            Schema::create('op_task_image', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('op_task_id');
                $table->string('mime_type', 80)->default('image/png');
                $table->binary('image_data');
                $table->timestamp('created_at')->useCurrent();

                $table->index('op_task_id');
                $table->foreign('op_task_id')->references('id')->on('op_tasks');
            });
        }

        if (! Schema::hasTable('deleted_entity_log')) {
            Schema::create('deleted_entity_log', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 32);
                $table->unsignedBigInteger('entity_id');
                $table->unsignedBigInteger('parent_entity_id')->nullable();
                $table->string('deleted_by', 120)->default('');
                $table->timestamp('deleted_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('updated_at');
                $table->index(['entity_type', 'entity_id']);
                $table->index('parent_entity_id');
            });
        }

        if (! Schema::hasTable('chat_message')) {
            Schema::create('chat_message', function (Blueprint $table) {
                $table->id();
                $table->string('username', 120);
                $table->string('display_name', 120)->default('');
                $table->text('message');
                $table->string('image_mime', 80)->default('');
                $table->binary('image_data')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('created_at');
                $table->index('username');
            });
        }

        if (! Schema::hasTable('calendar_notes')) {
            Schema::create('calendar_notes', function (Blueprint $table) {
                $table->increments('id');
                $table->date('date');
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('priority', 24)->default('Média');
                $table->string('createdAt', 64)->default('');
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('date');
            });
        }

        if (! Schema::hasTable('escalas')) {
            Schema::create('escalas', function (Blueprint $table) {
                $table->id();
                $table->string('client_uid', 48);
                $table->date('data')->nullable();
                $table->unsignedTinyInteger('mes');
                $table->unsignedTinyInteger('dia_semana');
                $table->time('horario');
                $table->time('horario_inicio')->nullable();
                $table->time('horario_fim')->nullable();
                $table->decimal('horas', 5, 2)->default(1.00);
                $table->string('nome', 120);
                $table->string('created_by', 120)->default('');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->unique('client_uid');
                $table->index('nome');
                $table->index('mes');
                $table->index('data');
                $table->index(['nome', 'data']);
                $table->index('updated_at');
                $table->index(['mes', 'dia_semana', 'horario']);
                $table->index(['horario_inicio', 'horario_fim']);
            });
        }

        if (! Schema::hasTable('eventos')) {
            Schema::create('eventos', function (Blueprint $table) {
                $table->id();
                $table->string('titulo', 255);
                $table->text('descricao')->nullable();
                $table->dateTime('data_inicio');
                $table->dateTime('data_fim')->nullable();
                $table->string('categoria', 32)->default('Em andamento');
                $table->timestamp('criado_em')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('data_inicio');
                $table->index('categoria');
            });
        }

        if (! Schema::hasTable('bm_room')) {
            Schema::create('bm_room', function (Blueprint $table) {
                $table->id();
                $table->string('code', 16);
                $table->string('host_user', 120);
                $table->string('guest_user', 120)->nullable();
                $table->integer('seed');
                $table->string('status', 16)->default('lobby');
                $table->bigInteger('start_at_ms')->default(0);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->unique('code');
                $table->index('status');
                $table->index('host_user');
                $table->index('guest_user');
            });
        }

        if (! Schema::hasTable('bm_input')) {
            Schema::create('bm_input', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('room_id');
                $table->integer('tick');
                $table->string('username', 120);
                $table->string('action', 24);
                $table->longText('payload')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['room_id', 'tick', 'username', 'action'], 'uniq_bm_input_once');
                $table->index(['room_id', 'tick']);
                $table->foreign('room_id')->references('id')->on('bm_room')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('schema_migrations')) {
            Schema::create('schema_migrations', function (Blueprint $table) {
                $table->string('migration', 120)->primary();
                $table->timestamp('applied_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bm_input');
        Schema::dropIfExists('bm_room');
        Schema::dropIfExists('eventos');
        Schema::dropIfExists('escalas');
        Schema::dropIfExists('calendar_notes');
        Schema::dropIfExists('chat_message');
        Schema::dropIfExists('deleted_entity_log');
        Schema::dropIfExists('op_task_image');
        Schema::dropIfExists('app_notification');
        Schema::dropIfExists('app_activity_event');
        Schema::dropIfExists('app_config');
        Schema::dropIfExists('os_tecnicos');
        Schema::dropIfExists('op_tasks');
        Schema::dropIfExists('usuario');
        Schema::dropIfExists('schema_migrations');
    }
};
