<?php

declare(strict_types=1);

namespace Database\Seeders;

use Core\Database;
use PDO;

final class MaintainerSeeder
{
    public function run(): void
    {
        $data = [
            'patient_statuses' => [
                ['activo', 'En atencion', 'Caso actualmente en atencion.', 'green', 10],
                ['pausado', 'Pausado', 'Caso temporalmente suspendido.', 'amber', 20],
                ['cerrado', 'Cerrado', 'Caso cerrado clinicamente.', 'gray', 30],
            ],
            'session_modalities' => [
                ['presencial', 'Presencial', 'Sesion realizada presencialmente.', 'green', 10],
                ['online', 'Online', 'Sesion realizada por videollamada u otro medio online.', 'blue', 20],
                ['telefonica', 'Telefonica', 'Sesion realizada por telefono.', 'amber', 30],
            ],
            'risk_levels' => [
                ['sin_riesgo', 'Sin riesgo observado', 'Sin riesgo clinico observado en la sesion.', 'green', 10],
                ['bajo', 'Bajo', 'Riesgo bajo.', 'green', 20],
                ['medio', 'Medio', 'Riesgo medio que requiere seguimiento.', 'amber', 30],
                ['alto', 'Alto', 'Riesgo alto que requiere protocolo clinico.', 'red', 40],
                ['critico', 'Critico', 'Riesgo critico que requiere accion inmediata.', 'red', 50],
            ],
            'consent_types' => [
                ['registro_clinico', 'Registro clinico', 'Consentimiento para registro clinico.', 'green', 10],
                ['tratamiento_datos', 'Tratamiento de datos', 'Consentimiento para tratamiento de datos personales.', 'amber', 20],
                ['analisis_ia', 'Analisis asistido por IA', 'Consentimiento especifico para uso de IA.', 'red', 30],
            ],
            'session_participant_types' => [
                ['paciente', 'Paciente', 'Paciente titular de la ficha.', 'green', 10],
                ['padre', 'Padre', 'Padre o figura paterna.', 'blue', 20],
                ['madre', 'Madre', 'Madre o figura materna.', 'blue', 30],
                ['padrastro', 'Padrastro', 'Pareja de madre con rol familiar.', 'amber', 40],
                ['madrastra', 'Madrastra', 'Pareja de padre con rol familiar.', 'amber', 50],
                ['abuelo', 'Abuelo', 'Abuelo u otro adulto mayor significativo.', 'gray', 60],
                ['abuela', 'Abuela', 'Abuela u otra adulta mayor significativa.', 'gray', 70],
                ['pareja', 'Pareja / pololo(a)', 'Pareja, pololo o polola.', 'red', 80],
                ['tio', 'Tio', 'Tio u otro familiar cercano.', 'gray', 90],
                ['tia', 'Tia', 'Tia u otra familiar cercana.', 'gray', 100],
                ['otro', 'Otro acompanante', 'Otro participante relevante para la sesion.', 'gray', 110],
            ],
            'session_topic_types' => [
                ['motivo_consulta', 'Motivos tipicos de sesion', 'Temas frecuentes asociados al motivo principal de sesion.', 'blue', 10],
                ['familia_vinculos', 'Problemas familiares y vinculos', 'Temas familiares, vinculares y de red de apoyo.', 'green', 20],
                ['infancia_adolescencia', 'Infancia y adolescencia', 'Temas propios del desarrollo infantojuvenil.', 'amber', 30],
                ['pareja_sexualidad', 'Pareja y sexualidad', 'Temas de pareja, intimidad y sexualidad.', 'red', 40],
                ['trabajo_estudios', 'Trabajo y estudios', 'Temas laborales, academicos y vocacionales.', 'blue', 50],
                ['riesgo_seguridad', 'Riesgo y seguridad', 'Temas que requieren evaluacion de riesgo o protocolo.', 'red', 60],
                ['trauma_eventos_vitales', 'Trauma y eventos vitales', 'Eventos adversos, perdidas y cambios significativos.', 'amber', 70],
                ['habitos_salud', 'Habitos y salud', 'Rutinas, autocuidado, salud fisica y consumo.', 'green', 80],
                ['proceso_terapeutico', 'Proceso terapeutico', 'Objetivos, tecnicas, acuerdos y seguimiento terapeutico.', 'gray', 90],
            ],
            'user_roles' => [
                ['administrador', 'Administrador', 'Usuario con administracion completa del sistema.', 'red', 10],
                ['profesional', 'Profesional', 'Profesional clinico responsable de pacientes.', 'green', 20],
                ['supervisor', 'Supervisor', 'Usuario con funciones de supervision clinica.', 'blue', 30],
            ],
            'user_statuses' => [
                ['activo', 'Activo', 'Usuario habilitado para usar el sistema.', 'green', 10],
                ['inactivo', 'Inactivo', 'Usuario deshabilitado para iniciar sesion.', 'gray', 20],
            ],
            'ai_review_statuses' => [
                ['pendiente', 'Pendiente', 'Salida IA pendiente de revision profesional.', 'amber', 10],
                ['aceptado', 'Aceptado', 'Salida IA aceptada por profesional.', 'green', 20],
                ['editado', 'Editado', 'Salida IA editada por profesional.', 'blue', 30],
                ['descartado', 'Descartado', 'Salida IA descartada por profesional.', 'gray', 40],
            ],
        ];

        $db = Database::connection();

        foreach ($data as $table => $rows) {
            $statement = $this->statement($db, $table);

            foreach ($rows as [$code, $name, $description, $color, $sortOrder]) {
                $params = [
                    'code' => $code,
                    'name' => $name,
                    'description' => $description,
                    'color' => $color,
                    'sort_order' => $sortOrder,
                ];

                $statement->execute($params);
            }
        }

        $this->seedSessionTopicSubtypes($db);
    }

    private function seedSessionTopicSubtypes(PDO $db): void
    {
        $data = [
            'motivo_consulta' => [
                'Ansiedad',
                'Estado de animo bajo',
                'Estres',
                'Crisis emocional',
                'Dificultades de adaptacion',
                'Duelo',
                'Autoestima',
                'Problemas de sueno',
                'Somatizacion',
                'Irritabilidad',
                'Desmotivacion',
            ],
            'familia_vinculos' => [
                'Relacion con madre',
                'Relacion con padre',
                'Relacion con hijos',
                'Relacion con pareja',
                'Separacion familiar',
                'Conflicto familiar',
                'Crianza',
                'Limites familiares',
                'Comunicacion familiar',
                'Violencia intrafamiliar',
                'Red de apoyo',
            ],
            'infancia_adolescencia' => [
                'Conducta escolar',
                'Bullying',
                'Apego',
                'Regulacion emocional',
                'Identidad',
                'Autonomia',
                'Normas y limites',
                'Uso de pantallas',
                'Relacion con pares',
                'Dificultades de aprendizaje',
            ],
            'pareja_sexualidad' => [
                'Conflicto de pareja',
                'Separacion',
                'Celos',
                'Dependencia emocional',
                'Comunicacion de pareja',
                'Intimidad',
                'Sexualidad',
                'Infidelidad',
                'Proyecto de vida en pareja',
            ],
            'trabajo_estudios' => [
                'Estres laboral',
                'Burnout',
                'Conflicto laboral',
                'Cesantia',
                'Rendimiento academico',
                'Orientacion vocacional',
                'Sobrecarga',
                'Dificultad de concentracion',
                'Procrastinacion',
            ],
            'riesgo_seguridad' => [
                'Ideacion suicida',
                'Autolesiones',
                'Crisis de panico',
                'Consumo problematico',
                'Violencia',
                'Abuso',
                'Negligencia',
                'Riesgo social',
                'Plan de seguridad',
                'Derivacion urgente',
            ],
            'trauma_eventos_vitales' => [
                'Trauma',
                'Abuso sexual',
                'Accidente',
                'Enfermedad grave',
                'Perdida significativa',
                'Migracion',
                'Separacion',
                'Cambio de etapa vital',
                'Experiencias adversas tempranas',
            ],
            'habitos_salud' => [
                'Sueno',
                'Alimentacion',
                'Actividad fisica',
                'Consumo de alcohol',
                'Consumo de drogas',
                'Medicacion',
                'Dolor cronico',
                'Enfermedad medica',
                'Rutinas',
                'Autocuidado',
            ],
            'proceso_terapeutico' => [
                'Objetivos terapeuticos',
                'Psicoeducacion',
                'Estrategias de afrontamiento',
                'Regulacion emocional',
                'Reestructuracion cognitiva',
                'Habilidades sociales',
                'Tareas terapeuticas',
                'Seguimiento de acuerdos',
                'Alta terapeutica',
                'Derivacion',
            ],
        ];

        $typeStatement = $db->prepare('SELECT id FROM session_topic_types WHERE code = :code LIMIT 1');
        $subtypeStatement = $db->prepare(
            'INSERT INTO session_topic_subtypes (
                code,
                name,
                description,
                color,
                sort_order,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :code,
                :name,
                NULL,
                NULL,
                :sort_order,
                1,
                NOW(),
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                sort_order = VALUES(sort_order),
                is_active = 1,
                updated_at = NOW()'
        );
        $subtypeIdStatement = $db->prepare('SELECT id FROM session_topic_subtypes WHERE code = :code LIMIT 1');
        $relationIdStatement = $db->prepare(
            'SELECT id
             FROM session_topic_type_subtypes
             WHERE session_topic_type_id = :session_topic_type_id
               AND session_topic_subtype_id = :session_topic_subtype_id
               AND created_by_user_id IS NULL
             LIMIT 1'
        );
        $relationUpdateStatement = $db->prepare(
            'UPDATE session_topic_type_subtypes
             SET is_active = 1,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $relationInsertStatement = $db->prepare(
            'INSERT INTO session_topic_type_subtypes (
                session_topic_type_id,
                session_topic_subtype_id,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :session_topic_type_id,
                :session_topic_subtype_id,
                1,
                NOW(),
                NOW()
             )'
        );

        foreach ($data as $typeCode => $subtypes) {
            $typeStatement->execute(['code' => $typeCode]);
            $typeId = (int) $typeStatement->fetchColumn();

            if ($typeId === 0) {
                continue;
            }

            foreach ($subtypes as $index => $name) {
                $code = $this->slug($name);
                $subtypeStatement->execute([
                    'code' => $code,
                    'name' => $name,
                    'sort_order' => ($index + 1) * 10,
                ]);
                $subtypeIdStatement->execute(['code' => $code]);
                $subtypeId = (int) $subtypeIdStatement->fetchColumn();

                if ($subtypeId === 0) {
                    continue;
                }

                $relationParams = [
                    'session_topic_type_id' => $typeId,
                    'session_topic_subtype_id' => $subtypeId,
                ];
                $relationIdStatement->execute($relationParams);
                $relationId = (int) $relationIdStatement->fetchColumn();

                if ($relationId > 0) {
                    $relationUpdateStatement->execute(['id' => $relationId]);
                    continue;
                }

                $relationInsertStatement->execute($relationParams);
            }
        }
    }

    private function slug(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace(
            [' ', '/', '(', ')', '.', ',', 'á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['_', '_', '', '', '', '', 'a', 'e', 'i', 'o', 'u', 'n'],
            $value
        );

        return preg_replace('/[^a-z0-9_]+/', '', $value) ?: 'tema';
    }

    private function statement(PDO $db, string $table): \PDOStatement
    {
        if ($table === 'session_topic_types') {
            return $db->prepare(
                "INSERT INTO {$table} (
                    code,
                    name,
                    description,
                    color,
                    sort_order,
                    is_active,
                    created_at,
                    updated_at
                 )
                 VALUES (
                    :code,
                    :name,
                    :description,
                    :color,
                    :sort_order,
                    1,
                    NOW(),
                    NOW()
                 )
                 ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    description = VALUES(description),
                    color = VALUES(color),
                    sort_order = VALUES(sort_order),
                    is_active = 1,
                    updated_at = NOW()"
            );
        }

        return $db->prepare(
            "INSERT INTO {$table} (
                code,
                name,
                description,
                color,
                sort_order,
                is_active,
                created_at,
                updated_at
             )
             VALUES (
                :code,
                :name,
                :description,
                :color,
                :sort_order,
                1,
                NOW(),
                NOW()
             )
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                color = VALUES(color),
                sort_order = VALUES(sort_order),
                is_active = 1,
                updated_at = NOW()"
        );
    }
}
