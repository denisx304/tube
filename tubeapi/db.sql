-- MySQL Script generated by MySQL Workbench
-- 03/31/15 23:26:36
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema tubedb
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema tubedb
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `tubedb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `tubedb` ;

-- -----------------------------------------------------
-- Table `tubedb`.`users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tubedb`.`users` ;

CREATE TABLE IF NOT EXISTS `tubedb`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(45) NOT NULL,
  `password` VARCHAR(45) NOT NULL,
  `reg_date` TIMESTAMP NOT NULL DEFAULT NOW(),
  `karma` INT NULL,
  `path_of_avatar` VARCHAR(256) NULL,
  `description` VARCHAR(128) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `tubedb`.`videos`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tubedb`.`videos` ;

CREATE TABLE IF NOT EXISTS `tubedb`.`videos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `title` VARCHAR(45) NOT NULL,
  `description` VARCHAR(256) NULL,
  `path_of_video` VARCHAR(256) NOT NULL,
  `likes` INT NOT NULL DEFAULT 0,
  `dislikes` INT NOT NULL DEFAULT 0,
  `post_date` TIMESTAMP NOT NULL DEFAULT NOW(),
  `views` INT NOT NULL DEFAULT 0,
  `no_of_com` INT NOT NULL DEFAULT 0,
  `path_of_pic` VARCHAR(256) NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_videos_user_idx` (`user_id` ASC),
  CONSTRAINT `fk_videos_users`
    FOREIGN KEY (`user_id`)
    REFERENCES `tubedb`.`users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `tubedb`.`comments`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tubedb`.`comments` ;

CREATE TABLE IF NOT EXISTS `tubedb`.`comments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `video_id` INT NOT NULL,
  `text` VARCHAR(256) NOT NULL,
  `likes` INT NOT NULL DEFAULT 0,
  `dislikes` INT NOT NULL DEFAULT 0,
  `post_date` TIMESTAMP NOT NULL DEFAULT NOW(),
  PRIMARY KEY (`id`),
  INDEX `fk_comments_videos1_idx` (`video_id` ASC),
  INDEX `fk_comments_users1_idx` (`user_id` ASC),
  CONSTRAINT `fk_comments_videos1`
    FOREIGN KEY (`video_id`)
    REFERENCES `tubedb`.`videos` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_comments_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `tubedb`.`users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `tubedb`.`favorites`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tubedb`.`favorites` ;

CREATE TABLE IF NOT EXISTS `tubedb`.`favorites` (
  `user_id` INT NOT NULL,
  `video_id` INT NOT NULL,
  INDEX `fk_favorites_users1_idx` (`user_id` ASC),
  INDEX `fk_favorites_videos1_idx` (`video_id` ASC),
  CONSTRAINT `fk_favorites_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `tubedb`.`users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_favorites_videos1`
    FOREIGN KEY (`video_id`)
    REFERENCES `tubedb`.`videos` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `tubedb`.`history`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tubedb`.`history` ;

CREATE TABLE IF NOT EXISTS `tubedb`.`history` (
  `user_id` INT NOT NULL,
  `video_id` INT NOT NULL,
  `view_date` TIMESTAMP NOT NULL DEFAULT NOW(),
  INDEX `fk_history_users1_idx` (`user_id` ASC),
  INDEX `fk_history_videos1_idx` (`video_id` ASC),
  CONSTRAINT `fk_history_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `tubedb`.`users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_history_videos1`
    FOREIGN KEY (`video_id`)
    REFERENCES `tubedb`.`videos` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


DROP TABLE IF EXISTS `tubedb`.`tokens` ;

-- -----------------------------------------------------
-- Table `tubedb`.`tokens`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tubedb`.`tokens` (
   `user_id` INT NOT NULL,
   `content` VARCHAR(512) NOT NULL,
   `expire_date` INT NOT NULL,
    PRIMARY KEY (`user_id`))     
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
