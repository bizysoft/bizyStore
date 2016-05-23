-- 
-- Configuration as per the unitTestExample.xml file provided.
-- This file has been run into the bizy.db example database which is also the default for unit tests.
--

CREATE TABLE member (
  id INTEGER PRIMARY KEY NOT NULL,
  dateCreated DATE DEFAULT (datetime('now','localtime')),
  firstName varchar(80),
  lastName varchar(80),
  address varchar(100),
  suburb varchar(50),
  state varchar(10),
  postCode varchar(10),
  email varchar(100),
  phoneNo varchar(30),
  gender varchar(10),
  dob TEXT
);

CREATE TABLE membership (
  id INTEGER PRIMARY KEY NOT NULL,
  dateCreated DATE DEFAULT (datetime('now','localtime')),
  length INTEGER,
  memberId INTEGER REFERENCES member(id),
  adminId INTEGER REFERENCES member(id)
);

CREATE TABLE multiPrimaryKeyMember (
  id INTEGER NOT NULL,
  dateCreated DATE DEFAULT (datetime('now','localtime')),
  firstName varchar(80),
  lastName varchar(80),
  address varchar(100),
  suburb varchar(50),
  state varchar(10),
  postCode varchar(10),
  email varchar(100),
  phoneNo varchar(30),
  gender varchar(10),
  dob TEXT,
  primary key (id, email)
);

CREATE TABLE overlappedUniqueKeyMember
(
  dateCreated DATE DEFAULT (datetime('now','localtime')),
  firstName varchar(80),
  lastName varchar(80),
  address varchar(100),
  suburb varchar(50),
  state varchar(10),
  postCode varchar(10),
  email varchar(100),
  phoneNo varchar(30),
  gender varchar(10),
  dob TEXT,
  unique(email, phoneNo, dob),
  unique(firstName, lastName, dob)
);

CREATE TABLE uniqueKeyMember (
  dateCreated DATE DEFAULT (datetime('now','localtime')),
  firstName varchar(80),
  lastName varchar(80),
  address varchar(100),
  suburb varchar(50),
  state varchar(10),
  postCode varchar(10),
  email varchar(100),
  phoneNo varchar(30),
  gender varchar(10),
  dob TEXT,
  unique(firstName, lastName, dob)
);

-- Foreign keys are defined in the bizySoftConfig file for this database table
CREATE TABLE uniqueKeyMembership (
  dateCreated DATE DEFAULT (datetime('now','localtime')),
  length INTEGER,
  memberFirstName varchar(80),
  memberLastName varchar(80),
  memberDob TEXT
);

CREATE TABLE versionedMember (
  id INTEGER PRIMARY KEY NOT NULL,
  dateCreated DATE DEFAULT (datetime('now','localtime')),
  firstName varchar(80),
  lastName varchar(80),
  address varchar(100),
  suburb varchar(50),
  state varchar(10),
  postCode varchar(10),
  email varchar(100),
  phoneNo varchar(30),
  gender varchar(10),
  dob TEXT,
  version int DEFAULT 0
);

CREATE VIEW memberView as select * from member;
CREATE VIEW membershipView as select * from membership;
