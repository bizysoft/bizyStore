-- 
-- Configuration as per the unitTestExample.xml file provided.
-- Run this file into a MySQL database if you require unit tests for that interface.
-- 

CREATE TABLE member
(
	id int PRIMARY KEY AUTO_INCREMENT,
	dateCreated timestamp DEFAULT CURRENT_TIMESTAMP,
	firstName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	lastName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	address varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	suburb varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL,
	state varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	postCode varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	email varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	phoneNo varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
	gender varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	dob date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE membership (
	id int PRIMARY KEY AUTO_INCREMENT,
	dateCreated timestamp DEFAULT CURRENT_TIMESTAMP,
	length int,
	memberId int,
	adminId int,
	FOREIGN KEY (memberId) REFERENCES member(id),
	FOREIGN KEY (adminId) REFERENCES member(id),
	INDEX(memberId),
	INDEX(adminId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE multiPrimaryKeyMember
(
	id int AUTO_INCREMENT,
	dateCreated timestamp DEFAULT CURRENT_TIMESTAMP,
	firstName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	lastName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	address varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	suburb varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL,
	state varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	postCode varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	email varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	phoneNo varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
	gender varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	dob date DEFAULT NULL,
	PRIMARY KEY (id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE overlappedUniqueKeyMember
(
	dateCreated timestamp DEFAULT CURRENT_TIMESTAMP,
	firstName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	lastName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	address varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	suburb varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL,
	state varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	postCode varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	email varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	phoneNo varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
	gender varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	dob date DEFAULT NULL,
	unique(email, phoneNo, dob),
	unique(firstName, lastName, dob)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE uniqueKeyMember
(
	dateCreated timestamp DEFAULT CURRENT_TIMESTAMP,
	firstName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	lastName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	address varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	suburb varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL,
	state varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	postCode varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	email varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	phoneNo varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
	gender varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	dob date DEFAULT NULL,
  	UNIQUE(firstName,lastName,dob)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign keys are defined in the bizySoftConfig file for this database table
CREATE TABLE uniqueKeyMembership (
	dateCreated timestamp DEFAULT CURRENT_TIMESTAMP,
	length INTEGER,
	memberFirstName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	memberLastName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	memberDob date DEFAULT NULL,
	INDEX(memberFirstName,memberLastName,memberDob)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE versionedMember
(
	id int PRIMARY KEY AUTO_INCREMENT,
	dateCreated timestamp DEFAULT CURRENT_TIMESTAMP,
	firstName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	lastName varchar(80) CHARACTER SET utf8mb4 DEFAULT NULL,
	address varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	suburb varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL,
	state varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	postCode varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	email varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
	phoneNo varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
	gender varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
	dob date DEFAULT NULL,
	version int DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE VIEW memberView as select * from member;

