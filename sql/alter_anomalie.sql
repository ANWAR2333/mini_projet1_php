USE gestion_factures;
ALTER TABLE anomalie
MODIFY type ENUM('index_inferieur','consommation_nulle','depasse_seuil') NOT NULL;
