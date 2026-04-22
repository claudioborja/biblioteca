/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: biblioteca
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user_action` (`user_id`,`action`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_date` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `fine_per_day_override` decimal(5,2) DEFAULT NULL,
  `loan_days_override` tinyint(4) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Literatura','literatura','Novelas, cuentos, poesía, teatro y obras literarias en general',NULL,NULL,'2026-04-13 23:46:44'),
(2,'Ciencias Naturales','ciencias-naturales','Biología, química, física, astronomía y ciencias de la tierra',NULL,NULL,'2026-04-13 23:46:44'),
(3,'Ciencias Sociales','ciencias-sociales','Sociología, antropología, ciencia política y economía',NULL,NULL,'2026-04-13 23:46:44'),
(4,'Historia','historia','Historia universal, nacional y regional',NULL,NULL,'2026-04-13 23:46:44'),
(5,'Matemáticas','matematicas','Álgebra, geometría, cálculo, estadística y matemáticas aplicadas',NULL,NULL,'2026-04-13 23:46:44'),
(6,'Tecnología','tecnologia','Informática, programación, ingeniería y tecnologías emergentes',NULL,NULL,'2026-04-13 23:46:44'),
(7,'Arte y Cultura','arte-y-cultura','Pintura, escultura, música, cine, fotografía y artes escénicas',NULL,NULL,'2026-04-13 23:46:44'),
(8,'Filosofía','filosofia','Filosofía clásica, moderna, contemporánea y ética',NULL,NULL,'2026-04-13 23:46:44'),
(9,'Psicología','psicologia','Psicología clínica, social, educativa y del desarrollo',NULL,NULL,'2026-04-13 23:46:44'),
(10,'Educación','educacion','Pedagogía, didáctica, formación docente y sistemas educativos',NULL,NULL,'2026-04-13 23:46:44'),
(11,'Idiomas','idiomas','Gramática, diccionarios, aprendizaje de lenguas extranjeras',NULL,NULL,'2026-04-13 23:46:44'),
(12,'Derecho','derecho','Legislación, derecho civil, penal, constitucional y laboral',NULL,NULL,'2026-04-13 23:46:44'),
(13,'Economía y Finanzas','economia-y-finanzas','Microeconomía, macroeconomía, contabilidad y finanzas personales',NULL,NULL,'2026-04-13 23:46:44'),
(14,'Salud y Medicina','salud-y-medicina','Medicina general, enfermería, nutrición y salud pública',NULL,NULL,'2026-04-13 23:46:44'),
(15,'Deportes','deportes','Educación física, deportes olímpicos y recreación',NULL,NULL,'2026-04-13 23:46:44'),
(16,'Geografía','geografia','Geografía física, humana, atlas y cartografía',NULL,NULL,'2026-04-13 23:46:44'),
(17,'Enciclopedias','enciclopedias','Obras de referencia general, enciclopedias y almanques',NULL,NULL,'2026-04-13 23:46:44'),
(18,'Infantil y Juvenil','infantil-y-juvenil','Libros para niños y jóvenes, cuentos ilustrados y fábulas',NULL,NULL,'2026-04-13 23:46:44'),
(19,'Biografías','biografias','Autobiografías, memorias y biografías de personajes notables',NULL,NULL,'2026-04-13 23:46:44'),
(20,'Religión y Espiritualidad','religion-y-espiritualidad','Teología, estudios religiosos comparados y espiritualidad',NULL,NULL,'2026-04-13 23:46:44');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `digital_access_log`
--

DROP TABLE IF EXISTS `digital_access_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_access_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` enum('view','download') NOT NULL DEFAULT 'view',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_digital_access_resource` (`resource_id`),
  KEY `idx_digital_access_user` (`user_id`),
  KEY `idx_digital_access_created` (`created_at`),
  CONSTRAINT `fk_digital_access_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_digital_access_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `digital_access_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `digital_access_log` WRITE;
/*!40000 ALTER TABLE `digital_access_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `digital_access_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `to_email` varchar(150) NOT NULL,
  `to_name` varchar(150) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `body_html` mediumtext NOT NULL,
  `body_text` mediumtext DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(4) NOT NULL DEFAULT 0,
  `scheduled_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_queue_status_scheduled` (`status`,`scheduled_at`),
  KEY `idx_email_queue_dispatch` (`status`,`priority`,`scheduled_at`,`attempts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_queue`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_queue` WRITE;
/*!40000 ALTER TABLE `email_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_queue` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email_verifications_user` (`user_id`),
  UNIQUE KEY `uq_email_verifications_token` (`token_hash`),
  KEY `idx_email_verifications_expires` (`expires_at`),
  CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_verifications` WRITE;
/*!40000 ALTER TABLE `email_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_verifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `fines`
--

DROP TABLE IF EXISTS `fines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `hours_overdue` int(11) NOT NULL DEFAULT 0,
  `replacement_cost_at_fine` decimal(8,2) NOT NULL,
  `reason` enum('overdue','damage','loss') NOT NULL DEFAULT 'overdue',
  `status` enum('pending','partially_paid','paid','waived') NOT NULL DEFAULT 'pending',
  `amount_paid` decimal(8,2) NOT NULL DEFAULT 0.00,
  `waiver_reason` text DEFAULT NULL,
  `waived_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_fines_waived_by` (`waived_by`),
  KEY `idx_fines_user_status` (`user_id`,`status`),
  KEY `idx_fines_loan` (`loan_id`),
  CONSTRAINT `fk_fines_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fines_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fines_waived_by` FOREIGN KEY (`waived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fines`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `fines` WRITE;
/*!40000 ALTER TABLE `fines` DISABLE KEYS */;
/*!40000 ALTER TABLE `fines` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `library_branches`
--

DROP TABLE IF EXISTS `library_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_branches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `schedule` text DEFAULT NULL,
  `manager_id` int(10) unsigned DEFAULT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branches_code` (`code`),
  UNIQUE KEY `uq_branches_name` (`name`),
  KEY `fk_branches_manager` (`manager_id`),
  CONSTRAINT `fk_branches_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_branches`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `library_branches` WRITE;
/*!40000 ALTER TABLE `library_branches` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_branches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `librarian_id` int(10) unsigned DEFAULT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `loan_at` datetime NOT NULL DEFAULT current_timestamp(),
  `due_at` datetime NOT NULL,
  `returned_at` datetime DEFAULT NULL,
  `loan_hours_applied` smallint(6) NOT NULL DEFAULT 72,
  `renewals_count` tinyint(4) NOT NULL DEFAULT 0,
  `status` enum('active','returned','overdue','lost') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_loans_librarian` (`librarian_id`),
  KEY `idx_loans_user_status` (`user_id`,`status`),
  KEY `idx_loans_due_status` (`due_at`,`status`),
  KEY `idx_loans_branch_status` (`branch_id`,`status`),
  KEY `idx_loans_resource_status` (`resource_id`,`status`),
  CONSTRAINT `fk_loans_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_loans_librarian` FOREIGN KEY (`librarian_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_loans_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_loans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'001_create_categories.sql','2026-04-13 23:43:35'),
(2,'002_create_library_branches.sql','2026-04-13 23:43:35'),
(3,'003_create_users.sql','2026-04-13 23:43:35'),
(4,'004_create_resources.sql','2026-04-13 23:43:35'),
(5,'005_create_loans.sql','2026-04-13 23:44:05'),
(6,'006_create_reservations.sql','2026-04-13 23:44:44'),
(7,'007_create_fines.sql','2026-04-13 23:44:44'),
(8,'008_create_email_queue.sql','2026-04-13 23:44:44'),
(9,'009_create_audit_logs.sql','2026-04-13 23:44:44'),
(10,'010_create_system_settings.sql','2026-04-13 23:44:44'),
(11,'011_create_password_resets.sql','2026-04-13 23:44:44'),
(12,'012_create_search_log.sql','2026-04-13 23:44:44'),
(13,'013_create_news.sql','2026-04-13 23:44:44'),
(14,'014_create_visits_log.sql','2026-04-13 23:44:44'),
(15,'015_create_teacher_groups.sql','2026-04-13 23:44:44'),
(16,'016_create_teacher_group_students.sql','2026-04-13 23:44:44'),
(17,'017_create_reading_assignments.sql','2026-04-13 23:45:03'),
(18,'018_create_reading_assignment_students.sql','2026-04-13 23:45:03'),
(19,'019_create_resource_suggestions.sql','2026-04-13 23:45:03'),
(20,'020_create_digital_access_log.sql','2026-04-13 23:45:03'),
(21,'021_add_marc21_to_resources.sql','2026-04-13 23:45:03'),
(22,'022_backfill_marc21_resources.sql','2026-04-13 23:45:03'),
(23,'023_rebuild_marc21_existing_resources.sql','2026-04-13 23:45:03'),
(24,'024_add_rda_resource_fields.sql','2026-04-13 23:45:33'),
(25,'025_rename_books_to_resources.sql','2026-04-13 23:46:22'),
(26,'026_rename_member_to_user_fields.sql','2026-04-13 23:46:44'),
(27,'027_add_email_verified_at_to_users.sql','2026-04-13 23:46:44'),
(28,'027_add_smtp_settings.sql','2026-04-13 23:46:44'),
(29,'028_create_email_verifications.sql','2026-04-13 23:46:44'),
(30,'029_add_app_url_setting.sql','2026-04-13 23:46:44'),
(31,'030_add_priority_to_email_queue.sql','2026-04-13 23:46:44');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `content` text NOT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_slug` (`slug`),
  KEY `idx_news_published` (`is_published`,`published_at`),
  KEY `idx_news_author` (`author_id`),
  CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_token` (`token_hash`),
  KEY `idx_password_resets_user` (`user_id`),
  KEY `idx_password_resets_expires` (`expires_at`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reading_assignment_students`
--

DROP TABLE IF EXISTS `reading_assignment_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_assignment_students` (
  `assignment_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assignment_id`,`student_id`),
  KEY `idx_ras_student` (`student_id`),
  KEY `idx_ras_status` (`status`),
  CONSTRAINT `fk_ras_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `reading_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ras_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reading_assignment_students`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reading_assignment_students` WRITE;
/*!40000 ALTER TABLE `reading_assignment_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `reading_assignment_students` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reading_assignments`
--

DROP TABLE IF EXISTS `reading_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reading_assignments_group` (`group_id`),
  KEY `idx_reading_assignments_resource` (`resource_id`),
  KEY `idx_reading_assignments_due` (`due_date`),
  CONSTRAINT `fk_reading_assignments_group` FOREIGN KEY (`group_id`) REFERENCES `teacher_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reading_assignments_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reading_assignments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reading_assignments` WRITE;
/*!40000 ALTER TABLE `reading_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `reading_assignments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `queue_position` int(10) unsigned NOT NULL,
  `status` enum('waiting','notified','fulfilled','cancelled','expired') NOT NULL DEFAULT 'waiting',
  `notified_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reservations_resource_queue` (`resource_id`,`queue_position`,`status`),
  KEY `idx_reservations_user` (`user_id`,`status`),
  CONSTRAINT `fk_reservations_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `resource_suggestions`
--

DROP TABLE IF EXISTS `resource_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_suggestions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(17) DEFAULT NULL,
  `publisher` varchar(200) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','acquired') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_resource_suggestions_user` (`user_id`),
  KEY `idx_resource_suggestions_status` (`status`),
  KEY `idx_resource_suggestions_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_resource_suggestions_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_resource_suggestions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_suggestions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `resource_suggestions` WRITE;
/*!40000 ALTER TABLE `resource_suggestions` DISABLE KEYS */;
/*!40000 ALTER TABLE `resource_suggestions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `isbn_13` char(13) DEFAULT NULL,
  `marc_leader` char(24) DEFAULT NULL,
  `marc_control_number` varchar(64) DEFAULT NULL,
  `title` varchar(300) NOT NULL,
  `authors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`authors`)),
  `marc_record` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`marc_record`)),
  `publisher` varchar(200) DEFAULT NULL,
  `edition_statement` varchar(200) DEFAULT NULL,
  `publication_year` smallint(6) DEFAULT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `support_type` enum('physical','digital','audiovisual','journal','thesis','map','score','kit','game','other') NOT NULL DEFAULT 'physical',
  `resource_type` varchar(60) DEFAULT NULL,
  `content_type` varchar(80) DEFAULT NULL,
  `media_type` varchar(80) DEFAULT NULL,
  `carrier_type` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `pages` smallint(5) unsigned DEFAULT NULL,
  `language` char(2) NOT NULL DEFAULT 'es',
  `cover_image` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `digital_url` text DEFAULT NULL,
  `acquisition_price` decimal(8,2) DEFAULT NULL,
  `replacement_cost` decimal(8,2) NOT NULL,
  `acquisition_date` date DEFAULT NULL,
  `acquired_at` datetime DEFAULT NULL,
  `is_new_acquisition` tinyint(1) NOT NULL DEFAULT 1,
  `total_copies` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `available_copies` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `digital_access_count` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `deactivated_at` datetime DEFAULT NULL,
  `deactivated_by` int(10) unsigned DEFAULT NULL,
  `deactivation_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resources_isbn` (`isbn_13`),
  UNIQUE KEY `uq_books_marc_control_number` (`marc_control_number`),
  KEY `fk_resources_deactivated_by` (`deactivated_by`),
  KEY `idx_resources_support_active` (`support_type`,`is_active`),
  KEY `idx_resources_branch` (`branch_id`,`is_active`),
  KEY `idx_resources_new_acquisition` (`is_new_acquisition`,`acquired_at`),
  KEY `idx_resources_category` (`category_id`),
  FULLTEXT KEY `ft_resources` (`title`,`publisher`,`description`),
  CONSTRAINT `fk_resources_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_resources_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_resources_deactivated_by` FOREIGN KEY (`deactivated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resources`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `resources` WRITE;
/*!40000 ALTER TABLE `resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `resources` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `search_log`
--

DROP TABLE IF EXISTS `search_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `query` varchar(255) NOT NULL,
  `results` int(10) unsigned NOT NULL DEFAULT 0,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_search_log_user` (`user_id`),
  KEY `idx_search_log_created` (`created_at`),
  KEY `idx_search_log_query` (`query`(100)),
  CONSTRAINT `fk_search_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `search_log` WRITE;
/*!40000 ALTER TABLE `search_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL DEFAULT '',
  `type` enum('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES
('app_url','https://biblioteca.istel.edu.ec','string','2026-04-13 23:49:38'),
('block_loans_with_fines','true','boolean','2026-04-13 23:44:44'),
('carnet_prefix','BIB','string','2026-04-13 23:44:44'),
('currency_symbol','$','string','2026-04-13 23:44:44'),
('date_format','d/m/Y H:i','string','2026-04-13 23:44:44'),
('fine_per_hour','0.05','decimal','2026-04-13 23:44:44'),
('library_address','','string','2026-04-13 23:44:44'),
('library_email','','string','2026-04-13 23:44:44'),
('library_favicon','','string','2026-04-13 23:44:44'),
('library_logo','','string','2026-04-13 23:44:44'),
('library_name','','string','2026-04-13 23:44:44'),
('library_phone','','string','2026-04-13 23:44:44'),
('library_schedule','','string','2026-04-13 23:44:44'),
('library_slogan','','string','2026-04-13 23:44:44'),
('library_website','','string','2026-04-13 23:44:44'),
('loan_hours','72','integer','2026-04-13 23:44:44'),
('loan_hours_extended','120','integer','2026-04-13 23:44:44'),
('locale','es_MX','string','2026-04-13 23:44:44'),
('max_fine_multiplier','2.0','decimal','2026-04-13 23:44:44'),
('max_loans_per_user','3','integer','2026-04-13 23:44:44'),
('max_renewals','2','integer','2026-04-13 23:44:44'),
('new_acquisition_days','30','integer','2026-04-13 23:44:44'),
('news_on_home','3','integer','2026-04-13 23:44:44'),
('reminder_hours_before','24','integer','2026-04-13 23:44:44'),
('renewal_grace_hours','2','integer','2026-04-13 23:44:44'),
('reservation_hold_hours','48','integer','2026-04-13 23:44:44'),
('smtp_enabled','true','boolean','2026-04-13 23:44:44'),
('smtp_encryption','tls','string','2026-04-13 23:44:44'),
('smtp_from_address','no-reply@biblioteca.com','string','2026-04-13 23:44:44'),
('smtp_from_name','Biblioteca','string','2026-04-13 23:44:44'),
('smtp_host','','string','2026-04-13 23:44:44'),
('smtp_password','','string','2026-04-13 23:44:44'),
('smtp_port','587','integer','2026-04-13 23:44:44'),
('smtp_timeout','30','integer','2026-04-13 23:44:44'),
('smtp_username','','string','2026-04-13 23:44:44'),
('timezone','America/Mexico_City','string','2026-04-13 23:44:44');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `teacher_group_students`
--

DROP TABLE IF EXISTS `teacher_group_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_group_students` (
  `group_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`group_id`,`student_id`),
  KEY `idx_tgs_student` (`student_id`),
  CONSTRAINT `fk_tgs_group` FOREIGN KEY (`group_id`) REFERENCES `teacher_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tgs_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_group_students`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `teacher_group_students` WRITE;
/*!40000 ALTER TABLE `teacher_group_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_group_students` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `teacher_groups`
--

DROP TABLE IF EXISTS `teacher_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `school_year` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_teacher_groups_teacher` (`teacher_id`),
  KEY `idx_teacher_groups_year` (`school_year`),
  CONSTRAINT `fk_teacher_groups_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_groups`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `teacher_groups` WRITE;
/*!40000 ALTER TABLE `teacher_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_groups` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_number` varchar(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `document_number` varchar(30) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` enum('admin','librarian','teacher','user','guest') NOT NULL DEFAULT 'user',
  `user_type` enum('student','teacher','external','staff') NOT NULL DEFAULT 'student',
  `status` enum('active','suspended','blocked','inactive') NOT NULL DEFAULT 'active',
  `password_hash` varchar(255) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_document` (`document_number`),
  UNIQUE KEY `uq_users_user_number` (`user_number`),
  KEY `idx_users_role_status` (`role`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,NULL,'Administrador del Sistema','admin@biblioteca.local','ADMIN-001',NULL,NULL,NULL,NULL,'admin','student','active','$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM',NULL,NULL,1,NULL,NULL,'2026-04-13 23:46:44','2026-04-13 23:46:44'),
(2,NULL,'María García López','docente@biblioteca.local','DOC-001',NULL,NULL,NULL,NULL,'teacher','student','active','$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM',NULL,NULL,1,NULL,NULL,'2026-04-13 23:46:44','2026-04-13 23:46:44'),
(3,NULL,'Carlos Hernández Ruiz','estudiante1@biblioteca.local','EST-001',NULL,NULL,NULL,NULL,'user','student','active','$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM',NULL,NULL,1,NULL,NULL,'2026-04-13 23:46:44','2026-04-13 23:46:44'),
(4,NULL,'Ana Martínez Soto','estudiante2@biblioteca.local','EST-002',NULL,NULL,NULL,NULL,'user','student','active','$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM',NULL,NULL,1,NULL,NULL,'2026-04-13 23:46:44','2026-04-13 23:46:44'),
(5,NULL,'Luis Pérez Vega','estudiante3@biblioteca.local','EST-003',NULL,NULL,NULL,NULL,'user','student','active','$argon2id$v=19$m=65536,t=4,p=1$eDNsb05xcU5sSEE0RVlydg$EcbMwUq1cE4nTeYl725jRsd/hFKVULhoTdek+9wFWXM',NULL,NULL,1,NULL,NULL,'2026-04-13 23:46:44','2026-04-13 23:46:44');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `visits_log`
--

DROP TABLE IF EXISTS `visits_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visits_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `page` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `referer` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_visits_log_user` (`user_id`),
  KEY `idx_visits_log_branch` (`branch_id`),
  KEY `idx_visits_log_page` (`page`(100)),
  KEY `idx_visits_log_created` (`created_at`),
  CONSTRAINT `fk_visits_log_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_visits_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `visits_log` WRITE;
/*!40000 ALTER TABLE `visits_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `visits_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-04-13 23:54:06
