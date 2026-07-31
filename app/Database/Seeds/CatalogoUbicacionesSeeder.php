<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CatalogoUbicacionesSeeder extends Seeder
{
    public function run()
    {
        if (! $this->db->tableExists('cat_departamentos') || ! $this->db->tableExists('cat_municipios')) {
            return;
        }

        $catalogo = [
            'Alta Verapaz' => [
                'Coban', 'Santa Cruz Verapaz', 'San Cristobal Verapaz', 'Tactic', 'Tamahu', 'Tucuru', 'Panzos', 'Senahu',
                'San Pedro Carcha', 'San Juan Chamelco', 'Lanquin', 'Santa Maria Cahabon', 'Chisec', 'Chahal',
                'Fray Bartolome de las Casas', 'Santa Catalina La Tinta', 'Raxruha',
            ],
            'Baja Verapaz' => [
                'Salama', 'San Miguel Chicaj', 'Rabinal', 'Cubulco', 'Granados', 'Santa Cruz El Chol', 'San Jeronimo', 'Purulha',
            ],
            'Chimaltenango' => [
                'Chimaltenango', 'San Jose Poaquil', 'San Martin Jilotepeque', 'San Juan Comalapa', 'Santa Apolonia',
                'Tecpan Guatemala', 'Patzun', 'Pochuta', 'Patzicia', 'Santa Cruz Balanya', 'Acatenango', 'Yepocapa',
                'San Andres Itzapa', 'Parramos', 'Zaragoza', 'El Tejar',
            ],
            'Chiquimula' => [
                'Chiquimula', 'San Jose La Arada', 'San Juan Ermita', 'Jocotan', 'Camotan', 'Olopa', 'Esquipulas',
                'Concepcion Las Minas', 'Quezaltepeque', 'San Jacinto', 'Ipala',
            ],
            'El Progreso' => [
                'Guastatoya', 'Morazan', 'San Agustin Acasaguastlan', 'San Cristobal Acasaguastlan',
                'El Jicaro', 'Sansare', 'Sanarate', 'San Antonio La Paz',
            ],
            'Escuintla' => [
                'Escuintla', 'Santa Lucia Cotzumalguapa', 'La Democracia', 'Siquinala', 'Masagua', 'Tiquisate',
                'La Gomera', 'Guanagazapa', 'San Jose', 'Iztapa', 'Palin', 'San Vicente Pacaya', 'Nueva Concepcion', 'Sipacate',
            ],
            'Guatemala' => [
                'Guatemala', 'Santa Catarina Pinula', 'San Jose Pinula', 'San Jose del Golfo', 'Palencia', 'Chinautla',
                'San Pedro Ayampuc', 'Mixco', 'San Pedro Sacatepequez', 'San Juan Sacatepequez', 'San Raymundo',
                'Chuarrancho', 'Fraijanes', 'Amatitlan', 'Villa Nueva', 'Villa Canales', 'Petapa',
            ],
            'Huehuetenango' => [
                'Huehuetenango', 'Chiantla', 'Malacatancito', 'Cuilco', 'Nenton', 'San Pedro Necta', 'Jacaltenango',
                'San Pedro Soloma', 'San Ildefonso Ixtahuacan', 'Santa Barbara', 'La Libertad', 'La Democracia',
                'San Miguel Acatan', 'San Rafael La Independencia', 'Todos Santos Cuchumatan', 'San Juan Atitan',
                'Santa Eulalia', 'San Mateo Ixtatan', 'Colotenango', 'San Sebastian Huehuetenango', 'Tectitan',
                'Concepcion Huista', 'San Juan Ixcoy', 'San Antonio Huista', 'San Sebastian Coatan', 'Santa Cruz Barillas',
                'Aguacatan', 'San Rafael Petzal', 'San Gaspar Ixchil', 'Santiago Chimaltenango', 'Santa Ana Huista',
                'Union Cantinil', 'Petatan',
            ],
            'Izabal' => [
                'Puerto Barrios', 'Livingston', 'El Estor', 'Morales', 'Los Amates',
            ],
            'Jalapa' => [
                'Jalapa', 'San Pedro Pinula', 'San Luis Jilotepeque', 'San Manuel Chaparron',
                'San Carlos Alzatate', 'Monjas', 'Mataquescuintla',
            ],
            'Jutiapa' => [
                'Jutiapa', 'El Progreso', 'Santa Catarina Mita', 'Agua Blanca', 'Asuncion Mita', 'Yupiltepeque',
                'Atescatempa', 'Jerez', 'El Adelanto', 'Zapotitlan', 'Comapa', 'Jalpatagua', 'Conguaco', 'Moyuta',
                'Pasaco', 'San Jose Acatempa', 'Quesada',
            ],
            'Peten' => [
                'Flores', 'San Jose', 'San Benito', 'San Andres', 'La Libertad', 'San Francisco', 'Santa Ana',
                'Dolores', 'San Luis', 'Sayaxche', 'Melchor de Mencos', 'Poptun', 'Las Cruces', 'El Chal',
            ],
            'Quetzaltenango' => [
                'Quetzaltenango', 'Salcaja', 'Olintepeque', 'San Carlos Sija', 'Sibilia', 'Cabrican', 'Cajola',
                'San Miguel Siguila', 'Ostuncalco', 'San Mateo', 'Concepcion Chiquirichapa', 'San Martin Sacatepequez',
                'Almolonga', 'Cantel', 'Huitan', 'Zunil', 'Colomba', 'San Francisco La Union', 'El Palmar', 'Coatepeque',
                'Genova', 'Flores Costa Cuca', 'La Esperanza', 'Palestina de los Altos',
            ],
            'Quiche' => [
                'Santa Cruz del Quiche', 'Chiche', 'Chinique', 'Zacualpa', 'Chajul', 'Chichicastenango', 'Patzite',
                'San Antonio Ilotenango', 'San Pedro Jocopilas', 'Cunen', 'San Juan Cotzal', 'Joyabaj', 'Nebaj',
                'San Andres Sajcabaja', 'Uspantan', 'Sacapulas', 'San Bartolome Jocotenango', 'Canilla', 'Chicaman',
                'Ixcan', 'Pachalum',
            ],
            'Retalhuleu' => [
                'Retalhuleu', 'San Sebastian', 'Santa Cruz Mulua', 'San Martin Zapotitlan', 'San Felipe',
                'San Andres Villa Seca', 'Champerico', 'Nuevo San Carlos', 'El Asintal',
            ],
            'Sacatepequez' => [
                'Antigua Guatemala', 'Jocotenango', 'Pastores', 'Sumpango', 'Santo Domingo Xenacoj',
                'Santiago Sacatepequez', 'San Bartolome Milpas Altas', 'San Lucas Sacatepequez', 'Santa Lucia Milpas Altas',
                'Magdalena Milpas Altas', 'Santa Maria de Jesus', 'Ciudad Vieja', 'San Miguel Duenas', 'Alotenango',
                'San Antonio Aguas Calientes', 'Santa Catarina Barahona',
            ],
            'San Marcos' => [
                'San Marcos', 'San Pedro Sacatepequez', 'San Antonio Sacatepequez', 'Comitancillo', 'San Miguel Ixtahuacan',
                'Concepcion Tutuapa', 'Tacana', 'Sibinal', 'Tajumulco', 'Tejutla', 'San Rafael Pie de la Cuesta',
                'Nuevo Progreso', 'El Tumbador', 'El Rodeo', 'Malacatan', 'Catarina', 'Ayutla', 'Ocos', 'San Pablo',
                'El Quetzal', 'La Reforma', 'Pajapita', 'Ixchiguan', 'San Jose Ojetenam', 'San Cristobal Cucho',
                'Sipacapa', 'Esquipulas Palo Gordo', 'Rio Blanco', 'San Lorenzo', 'La Blanca',
            ],
            'Santa Rosa' => [
                'Cuilapa', 'Barberena', 'Santa Rosa de Lima', 'Casillas', 'San Rafael Las Flores', 'Oratorio',
                'San Juan Tecuaco', 'Chiquimulilla', 'Taxisco', 'Santa Maria Ixhuatan', 'Guazacapan',
                'Santa Cruz Naranjo', 'Pueblo Nuevo Vinas', 'Nueva Santa Rosa',
            ],
            'Solola' => [
                'Solola', 'San Jose Chacaya', 'Santa Maria Visitacion', 'Santa Lucia Utatlan', 'Nahuala',
                'Santa Catarina Ixtahuacan', 'Santa Clara La Laguna', 'Concepcion', 'San Andres Semetabaj',
                'Panajachel', 'Santa Catarina Palopo', 'San Antonio Palopo', 'San Lucas Toliman', 'Santa Cruz La Laguna',
                'San Pablo La Laguna', 'San Marcos La Laguna', 'San Juan La Laguna', 'San Pedro La Laguna',
                'Santiago Atitlan',
            ],
            'Suchitepequez' => [
                'Mazatenango', 'Cuyotenango', 'San Francisco Zapotitlan', 'San Bernardino', 'San Jose El Idolo',
                'Santo Domingo Suchitepequez', 'San Lorenzo', 'Samayac', 'San Pablo Jocopilas', 'San Antonio Suchitepequez',
                'San Miguel Panan', 'San Gabriel', 'Chicacao', 'Patulul', 'Santa Barbara', 'San Juan Bautista',
                'Santo Tomas La Union', 'Zunilito', 'Pueblo Nuevo', 'Rio Bravo', 'San Jose La Maquina',
            ],
            'Totonicapan' => [
                'Totonicapan', 'San Cristobal Totonicapan', 'San Francisco El Alto', 'San Andres Xecul', 'Momostenango',
                'Santa Maria Chiquimula', 'Santa Lucia La Reforma', 'San Bartolo Aguas Calientes',
            ],
            'Zacapa' => [
                'Zacapa', 'Estanzuela', 'Rio Hondo', 'Gualan', 'Teculutan', 'Usumatlan', 'Cabanas', 'San Diego',
                'La Union', 'Huite', 'San Jorge',
            ],
        ];

        $depTable = $this->db->table('cat_departamentos');
        $munTable = $this->db->table('cat_municipios');
        $now = date('Y-m-d H:i:s');

        foreach (array_keys($catalogo) as $index => $nombre) {
            $codigo = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

            $existing = $depTable->select('id')->where('nombre', $nombre)->get()->getRowArray();

            if (! is_array($existing)) {
                $depTable->insert([
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $departamentoId = (int) $this->db->insertID();
            } else {
                $departamentoId = (int) $existing['id'];
            }

            foreach (($catalogo[$nombre] ?? []) as $mIndex => $municipioNombre) {
                $munCodigo = $codigo . str_pad((string) ($mIndex + 1), 3, '0', STR_PAD_LEFT);

                $existsByCodigo = $munTable
                    ->select('id')
                    ->where('codigo', $munCodigo)
                    ->get()
                    ->getRowArray();

                if (is_array($existsByCodigo)) {
                    $munTable->where('id', (int) $existsByCodigo['id'])->update([
                        'departamento_id' => $departamentoId,
                        'nombre' => $municipioNombre,
                        'status' => 'active',
                        'updated_at' => $now,
                    ]);
                    continue;
                }

                $existsMunicipio = $munTable
                    ->select('id')
                    ->where('departamento_id', $departamentoId)
                    ->where('nombre', $municipioNombre)
                    ->get()
                    ->getRowArray();

                if (! is_array($existsMunicipio)) {
                    $munTable->insert([
                        'departamento_id' => $departamentoId,
                        'codigo' => $munCodigo,
                        'nombre' => $municipioNombre,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    continue;
                }

                $munTable->where('id', (int) $existsMunicipio['id'])->update([
                    'codigo' => $munCodigo,
                    'status' => 'active',
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
