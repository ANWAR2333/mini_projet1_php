
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 19 oct. 2026 à 15:11
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_factures`
--

DROP DATABASE IF EXISTS `gestion_factures`;
CREATE DATABASE `gestion_factures` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gestion_factures`;

-- --------------------------------------------------------

--
-- Structure de la table `agent`
--

CREATE TABLE `agent` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `anomalie`
--

CREATE TABLE `anomalie` (
  `id` int(11) NOT NULL,
  `id_consommation` int(11) NOT NULL,
  `service` enum('eau','electricite') NOT NULL,
  `type` enum('index_inferieur','consommation_nulle','depasse_seuil') NOT NULL,
  `index_precedent` decimal(10,2) NOT NULL,
  `index_saisi` decimal(10,2) NOT NULL,
  `index_corrige` decimal(10,2) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `date_detection` datetime DEFAULT current_timestamp(),
  `date_correction` datetime DEFAULT NULL,
  `statut` enum('a_traiter','corrigee') DEFAULT 'a_traiter'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `client`
--

CREATE TABLE `client` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `consommation`
--

CREATE TABLE `consommation` (
  `id` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `mois` tinyint(4) NOT NULL,
  `annee` smallint(6) NOT NULL,
  `index_eau` decimal(10,2) NOT NULL,
  `index_elec` decimal(10,2) NOT NULL,
  `photo_eau` varchar(255) DEFAULT NULL,
  `photo_elec` varchar(255) DEFAULT NULL,
  `date_saisie` datetime DEFAULT current_timestamp(),
  `statut` enum('normale','anomalie','corrigee') DEFAULT 'normale',
  CONSTRAINT `chk_mois` CHECK (`mois` BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `consommation_annuelle`
--

CREATE TABLE `consommation_annuelle` (
  `id` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_agent` int(11) NOT NULL,
  `conso_eau` decimal(10,2) NOT NULL,
  `conso_elec` decimal(10,2) NOT NULL,
  `annee` smallint(6) NOT NULL,
  `date_saisie` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `facture`
--

CREATE TABLE `facture` (
  `id` int(11) NOT NULL,
  `numero` varchar(30) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_consommation` int(11) NOT NULL,
  `date_facture` date NOT NULL,
  `periode` varchar(20) NOT NULL,
  `conso_eau` decimal(10,2) DEFAULT NULL,
  `conso_elec` decimal(10,2) DEFAULT NULL,
  `montant_ht` decimal(10,2) DEFAULT NULL,
  `tva` decimal(10,2) DEFAULT NULL,
  `montant_ttc` decimal(10,2) DEFAULT NULL,
  `statut` enum('impayee','payee') DEFAULT 'impayee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fournisseur`
--

CREATE TABLE `fournisseur` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_reclamation` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `date_envoi` datetime DEFAULT current_timestamp(),
  `lue` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

CREATE TABLE `paiement` (
  `id` int(11) NOT NULL,
  `id_facture` int(11) NOT NULL,
  `date_paiement` date NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `mode` enum('especes','carte','virement') DEFAULT 'especes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametre`
--

CREATE TABLE `parametre` (
  `id` int(11) NOT NULL DEFAULT 1,
  `seuil_eau` decimal(10,2) DEFAULT 50.00,
  `seuil_elec` decimal(10,2) DEFAULT 500.00,
  `taux_tva` decimal(4,2) DEFAULT 0.18
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `parametre`
--

INSERT INTO `parametre` (`id`, `seuil_eau`, `seuil_elec`, `taux_tva`) VALUES
(1, 50.00, 500.00, 0.18);

-- --------------------------------------------------------

--
-- Structure de la table `reclamation`
--

CREATE TABLE `reclamation` (
  `id` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_facture` int(11) DEFAULT NULL,
  `type` enum('fuite_externe','fuite_interne','facture','autre') NOT NULL,
  `autre_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_reclamation` datetime DEFAULT current_timestamp(),
  `statut` enum('en_attente','en_cours','traitee') DEFAULT 'en_attente',
  `reponse` text DEFAULT NULL,
  `date_reponse` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `agent`
--
ALTER TABLE `agent`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `anomalie`
--
ALTER TABLE `anomalie`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_consommation` (`id_consommation`);

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `consommation`
--
ALTER TABLE `consommation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_client` (`id_client`,`mois`,`annee`);

--
-- Index pour la table `consommation_annuelle`
--
ALTER TABLE `consommation_annuelle`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_client` (`id_client`,`annee`),
  ADD KEY `id_agent` (`id_agent`);

--
-- Index pour la table `facture`
--
ALTER TABLE `facture`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD UNIQUE KEY `id_consommation` (`id_consommation`),
  ADD KEY `id_client` (`id_client`);

--
-- Index pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_client` (`id_client`),
  ADD KEY `id_reclamation` (`id_reclamation`);

--
-- Index pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_facture` (`id_facture`);

--
-- Index pour la table `parametre`
--
ALTER TABLE `parametre`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_client` (`id_client`),
  ADD KEY `id_facture` (`id_facture`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `agent`
--
ALTER TABLE `agent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `anomalie`
--
ALTER TABLE `anomalie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `client`
--
ALTER TABLE `client`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `consommation`
--
ALTER TABLE `consommation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `consommation_annuelle`
--
ALTER TABLE `consommation_annuelle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `facture`
--
ALTER TABLE `facture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reclamation`
--
ALTER TABLE `reclamation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `anomalie`
--
ALTER TABLE `anomalie`
  ADD CONSTRAINT `anomalie_ibfk_1` FOREIGN KEY (`id_consommation`) REFERENCES `consommation` (`id`);

--
-- Contraintes pour la table `consommation`
--
ALTER TABLE `consommation`
  ADD CONSTRAINT `consommation_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`);

--
-- Contraintes pour la table `consommation_annuelle`
--
ALTER TABLE `consommation_annuelle`
  ADD CONSTRAINT `consommation_annuelle_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `consommation_annuelle_ibfk_2` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id`);

--
-- Contraintes pour la table `facture`
--
ALTER TABLE `facture`
  ADD CONSTRAINT `facture_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `facture_ibfk_2` FOREIGN KEY (`id_consommation`) REFERENCES `consommation` (`id`);

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamation` (`id`);

--
-- Contraintes pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `paiement_ibfk_1` FOREIGN KEY (`id_facture`) REFERENCES `facture` (`id`);

--
-- Contraintes pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD CONSTRAINT `reclamation_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `reclamation_ibfk_2` FOREIGN KEY (`id_facture`) REFERENCES `facture` (`id`);

--
-- ======================= PHASE 2 : CONTRATS, QUARTIERS, TYPES D'ACTIVITÉ =======================
-- Un client a un ou plusieurs contrats ; chaque contrat est dans un quartier ;
-- chaque quartier a un type d'activité (résidentiel, commercial, industriel) qui fixe le barème.
-- Les compteurs et index initiaux appartiennent désormais aux contrats, pas aux clients.
--

CREATE TABLE `type_activite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `seuil_eau` decimal(10,2) NOT NULL DEFAULT 50.00,    -- seuil d'anomalie (m³/mois), défini par le fournisseur
  `seuil_elec` decimal(10,2) NOT NULL DEFAULT 500.00,  -- seuil d'anomalie (kWh/mois)
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tranche_tarif` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_type_activite` int(11) NOT NULL,
  `service` enum('eau','electricite') NOT NULL,
  `borne_max` decimal(10,2) DEFAULT NULL,              -- NULL = sans limite (dernière tranche)
  `prix_unitaire` decimal(10,4) NOT NULL,
  `ordre` tinyint(4) NOT NULL,                         -- 1, 2, 3... pour trier les tranches
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_service_ordre` (`id_type_activite`,`service`,`ordre`),
  CONSTRAINT `tranche_tarif_ibfk_1` FOREIGN KEY (`id_type_activite`) REFERENCES `type_activite` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `quartier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `id_type_activite` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`),
  KEY `id_type_activite` (`id_type_activite`),
  CONSTRAINT `quartier_ibfk_1` FOREIGN KEY (`id_type_activite`) REFERENCES `type_activite` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `contrat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_quartier` int(11) NOT NULL,
  `num_compteur_eau` varchar(50) DEFAULT NULL,
  `num_compteur_elec` varchar(50) DEFAULT NULL,
  `index_initial_eau` decimal(10,2) NOT NULL DEFAULT 0.00,
  `index_initial_elec` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date_debut` date NOT NULL DEFAULT curdate(),
  `date_fin` date DEFAULT NULL,
  `statut` enum('actif','resilie') NOT NULL DEFAULT 'actif',
  PRIMARY KEY (`id`),
  UNIQUE KEY `num_compteur_eau` (`num_compteur_eau`),
  UNIQUE KEY `num_compteur_elec` (`num_compteur_elec`),
  KEY `id_client` (`id_client`),
  KEY `id_quartier` (`id_quartier`),
  -- pas de CASCADE : un contrat (même résilié) est conservé ; un client lié ne peut pas être supprimé
  CONSTRAINT `contrat_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`),
  CONSTRAINT `contrat_ibfk_2` FOREIGN KEY (`id_quartier`) REFERENCES `quartier` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- consommation : une saisie par contrat et par mois (id_contrat reste NULL pour d'éventuels relevés historiques)
ALTER TABLE `consommation`
  ADD COLUMN `id_contrat` int(11) DEFAULT NULL AFTER `id_client`,
  ADD KEY `id_client_idx` (`id_client`),
  ADD KEY `id_contrat` (`id_contrat`),
  ADD CONSTRAINT `consommation_ibfk_2` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id`);
ALTER TABLE `consommation` DROP INDEX `id_client`;
ALTER TABLE `consommation` ADD UNIQUE KEY `contrat_mois_annee` (`id_contrat`,`mois`,`annee`);

-- facture : contrat et montants figés par service (plus de recalcul à l'affichage)
ALTER TABLE `facture`
  ADD COLUMN `id_contrat` int(11) DEFAULT NULL AFTER `id_client`,
  ADD COLUMN `montant_eau` decimal(10,2) DEFAULT NULL AFTER `conso_elec`,
  ADD COLUMN `montant_elec` decimal(10,2) DEFAULT NULL AFTER `montant_eau`,
  ADD KEY `id_contrat` (`id_contrat`),
  ADD CONSTRAINT `facture_ibfk_3` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id`);

-- consommation_annuelle : relevé annuel par contrat (le fichier Consommation_Annuelle.txt contient ID_Contrat)
ALTER TABLE `consommation_annuelle`
  ADD COLUMN `id_contrat` int(11) DEFAULT NULL AFTER `id_client`,
  ADD KEY `id_client_idx` (`id_client`),
  ADD KEY `id_contrat` (`id_contrat`),
  ADD CONSTRAINT `consommation_annuelle_ibfk_3` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id`);
ALTER TABLE `consommation_annuelle` DROP INDEX `id_client`;
ALTER TABLE `consommation_annuelle` ADD UNIQUE KEY `contrat_annee` (`id_contrat`,`annee`);

-- reclamation : une fuite concerne un contrat précis (facultatif)
ALTER TABLE `reclamation`
  ADD COLUMN `id_contrat` int(11) DEFAULT NULL AFTER `id_facture`,
  ADD KEY `id_contrat` (`id_contrat`),
  ADD CONSTRAINT `reclamation_ibfk_3` FOREIGN KEY (`id_contrat`) REFERENCES `contrat` (`id`);

--
-- ======================= DONNÉES DE TEST (section 4 du cahier des charges) =======================
-- Fournisseur : fournisseur@ecohouse.ma / Four#2026
-- 5 clients (mot de passe Client#2026) avec leur consommation et facture de septembre 2026
--
INSERT INTO `fournisseur` (`id`,`nom`,`email`,`mot_de_passe`,`adresse`,`telephone`) VALUES
(1,'Amendis','fournisseur@ecohouse.ma','$2y$10$ChpC9FQkwmsK1g2lwzWIl.Vjvf3vPkSq9HOGVdkG6i5kNHA45i3Jy','Av. Abdelkhalek Torres, Tétouan','0539000000');

INSERT INTO `client` (`id`,`nom`,`prenom`,`adresse`,`telephone`,`email`,`mot_de_passe`) VALUES
(1,'Bennani','Salma','12 rue Mohammed V, Tétouan','0612345601','salma@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi'),
(2,'ElAmrani','Youssef','5 avenue des FAR, Tétouan','0612345602','youssef@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi'),
(3,'Berrada','Nadia','27 rue Al Massira, Tétouan','0612345603','nadia@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi'),
(4,'Tazi','Khadija','8 rue Ibn Battouta, Tétouan','0612345604','khadija@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi'),
(5,'Jemaoui','Mouad','33 boulevard Hassan II, Tétouan','0612345605','mouad@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi');
INSERT INTO `consommation` (`id`,`id_client`,`mois`,`annee`,`index_eau`,`index_elec`,`photo_eau`,`photo_elec`,`date_saisie`,`statut`) VALUES
(1,1,9,2026,1245.00,8430.00,NULL,NULL,'2026-09-24 10:00:00','normale'),
(2,2,9,2026,1250.00,6200.00,NULL,NULL,'2026-09-24 10:00:00','normale'),
(3,3,9,2026,640.00,3100.00,NULL,NULL,'2026-09-24 10:00:00','normale'),
(4,4,9,2026,410.00,5100.00,NULL,NULL,'2026-09-24 10:00:00','normale'),
(5,5,9,2026,850.00,2300.00,NULL,NULL,'2026-09-24 10:00:00','normale');
INSERT INTO `facture` (`id`,`numero`,`id_client`,`id_consommation`,`date_facture`,`periode`,`conso_eau`,`conso_elec`,`montant_ht`,`tva`,`montant_ttc`,`statut`) VALUES
(1,'F-0926-0001',1,1,'2026-09-30','09/2026',14.00,110.00,204.66,36.84,241.50,'payee'),
(2,'F-0926-0002',2,2,'2026-09-30','09/2026',12.00,130.00,208.28,37.49,245.77,'impayee'),
(3,'F-0926-0003',3,3,'2026-09-30','09/2026',8.00,95.00,137.02,24.66,161.68,'impayee'),
(4,'F-0926-0004',4,4,'2026-09-30','09/2026',10.00,140.00,202.70,36.49,239.19,'impayee'),
(5,'F-0926-0005',5,5,'2026-09-30','09/2026',22.00,160.00,417.56,75.16,492.72,'impayee');
INSERT INTO `paiement` (`id`,`id_facture`,`date_paiement`,`montant`,`mode`) VALUES
(1,1,'2026-10-02',241.50,'especes');
-- ======================= DONNÉES DE TEST PHASE 2 =======================
-- 3 types d'activité. Résidentiel = grille de l'énoncé.
-- Commercial et industriel : valeurs INVENTÉES par l'équipe (hypothèse à confirmer avec l'encadrant).
INSERT INTO `type_activite` (`id`,`nom`,`seuil_eau`,`seuil_elec`) VALUES
(1,'residentiel',50.00,500.00),
(2,'commercial',200.00,2000.00),
(3,'industriel',1000.00,10000.00);

INSERT INTO `tranche_tarif` (`id_type_activite`,`service`,`borne_max`,`prix_unitaire`,`ordre`) VALUES
(1,'electricite',100,0.8200,1),(1,'electricite',150,0.9200,2),(1,'electricite',NULL,1.1000,3),
(1,'eau',6,2.3700,1),(1,'eau',20,7.3900,2),(1,'eau',35,10.9800,3),(1,'eau',NULL,11.0300,4),
(2,'electricite',200,1.0000,1),(2,'electricite',400,1.1500,2),(2,'electricite',NULL,1.3000,3),
(2,'eau',10,3.2000,1),(2,'eau',30,8.9000,2),(2,'eau',60,12.5000,3),(2,'eau',NULL,13.2000,4),
(3,'electricite',1000,1.1000,1),(3,'electricite',5000,1.2500,2),(3,'electricite',NULL,1.4000,3),
(3,'eau',50,9.5000,1),(3,'eau',200,11.5000,2),(3,'eau',NULL,13.0000,3);

INSERT INTO `quartier` (`id`,`nom`,`id_type_activite`) VALUES
(1,'Centre-ville',1),
(2,'Al Massira',1),
(3,'Souk Jdid',2),
(4,'Zone industrielle Gzenaya',3);

-- Contrats 1 à 5 : un par client existant (même numéro que le client, donc anciens scénarios et numéros de facture inchangés)
-- Contrat 6 : Salma a un 2e contrat (commerce) dans un 2e quartier. Contrat 7 : ancien contrat RÉSILIÉ de Mouad.
INSERT INTO `contrat` (`id`,`id_client`,`id_quartier`,`num_compteur_eau`,`num_compteur_elec`,`index_initial_eau`,`index_initial_elec`,`date_debut`,`date_fin`,`statut`) VALUES
(1,1,1,'EAU-0001','ELEC-0001',1231.00,8320.00,'2026-01-01',NULL,'actif'),
(2,2,1,'EAU-0002','ELEC-0002',1238.00,6070.00,'2026-01-01',NULL,'actif'),
(3,3,2,'EAU-0003','ELEC-0003',632.00,3005.00,'2026-01-01',NULL,'actif'),
(4,4,2,'EAU-0004','ELEC-0004',400.00,4960.00,'2026-01-01',NULL,'actif'),
(5,5,2,'EAU-0005','ELEC-0005',828.00,2140.00,'2026-01-01',NULL,'actif'),
(6,1,3,'EAU-0006','ELEC-0006',100.00,200.00,'2026-10-01',NULL,'actif'),
(7,5,4,'EAU-0007','ELEC-0007',50.00,900.00,'2025-01-01','2026-06-30','resilie');

-- Les saisies et factures de septembre passent chacune par leur contrat
UPDATE `consommation`
SET `id_contrat` = `id_client`
WHERE `id_client` BETWEEN 1 AND 5 AND `mois` = 9 AND `annee` = 2026;
UPDATE `facture`
SET `id_contrat` = `id_client`,
    `montant_eau` = CASE `id`
      WHEN 1 THEN 103.46 WHEN 2 THEN 88.68 WHEN 3 THEN 59.12
      WHEN 4 THEN 73.90 WHEN 5 THEN 241.56
    END,
    `montant_elec` = CASE `id`
      WHEN 1 THEN 101.20 WHEN 2 THEN 119.60 WHEN 3 THEN 77.90
      WHEN 4 THEN 128.80 WHEN 5 THEN 176.00
    END
WHERE `id` BETWEEN 1 AND 5;

-- Un agent pour tester l'import du fichier annuel (mot de passe Client#2026, il ne se connecte pas)
INSERT INTO `agent` (`id`,`nom`,`prenom`,`email`,`mot_de_passe`) VALUES
(1,'Alaoui','Karim','agent@ecohouse.ma','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
 