--
-- Configuration as per the unitTestExample.xml file provided.
-- Run this file into a PostgreSQL database if you require unit tests for that interface.
--

CREATE TABLE member
(
    id SERIAL PRIMARY KEY,
    "dateCreated" timestamp without time zone DEFAULT LOCALTIMESTAMP,
    "firstName" character varying(80),
    "lastName" character varying(80),
    address character varying(100),
    suburb character varying(50),
    state character varying(10),
    "postCode" character varying(10),
    email character varying(100),
    "phoneNo" character varying(30),
    gender character varying(10),
    dob date
);

CREATE TABLE membership (
  id SERIAL PRIMARY KEY,
  "dateCreated" timestamp without time zone DEFAULT LOCALTIMESTAMP,
	length integer,
	"memberId" integer REFERENCES member(id) ON DELETE CASCADE,
	"adminId" integer REFERENCES member(id) ON DELETE NO ACTION
);

CREATE TABLE "multiPrimaryKeyMember"
(
    id SERIAL,
    "dateCreated" timestamp without time zone DEFAULT LOCALTIMESTAMP,
    "firstName" character varying(80),
    "lastName" character varying(80),
    address character varying(100),
    suburb character varying(50),
    state character varying(10),
    "postCode" character varying(10),
    email character varying(100),
    "phoneNo" character varying(30),
    gender character varying(10),
    dob date,
    PRIMARY KEY (id, email)
);

CREATE TABLE "overlappedUniqueKeyMember"
(
    "dateCreated" timestamp without time zone DEFAULT LOCALTIMESTAMP,
    "firstName" character varying(80),
    "lastName" character varying(80),
    address character varying(100),
    suburb character varying(50),
    state character varying(10),
    "postCode" character varying(10),
    email character varying(100),
    "phoneNo" character varying(30),
    gender character varying(10),
    dob date,
    unique(email, "phoneNo", dob),
    unique("firstName", "lastName", dob)
); 

CREATE TABLE "uniqueKeyMember"
(
    "dateCreated" timestamp without time zone DEFAULT LOCALTIMESTAMP,
    "firstName" character varying(80),
    "lastName" character varying(80),
    address character varying(100),
    suburb character varying(50),
    state character varying(10),
    "postCode" character varying(10),
    email character varying(100),
    "phoneNo" character varying(30),
    gender character varying(10),
    dob date,
    unique("firstName", "lastName", dob)
);

CREATE TABLE "uniqueKeyMembership" (
  "dateCreated"  timestamp without time zone DEFAULT LOCALTIMESTAMP,
  length INTEGER,
  "memberFirstName" character varying(80),
  "memberLastName" character varying(80),
  "memberDob" date,
  FOREIGN KEY ("memberFirstName", "memberLastName", "memberDob") REFERENCES "uniqueKeyMember"("firstName", "lastName", dob)
);

CREATE TABLE "versionedMember"
(
    id SERIAL PRIMARY KEY,
    "dateCreated" timestamp without time zone DEFAULT LOCALTIMESTAMP,
    "firstName" character varying(80),
    "lastName" character varying(80),
    address character varying(100),
    suburb character varying(50),
    state character varying(10),
    "postCode" character varying(10),
    email character varying(100),
    "phoneNo" character varying(30),
    gender character varying(10),
    dob date,
    version int default 0
);

CREATE SEQUENCE multisequencedmember_seq_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
    
CREATE TABLE "multiSequencedMember"
(
    id serial primary key,
    "dateCreated" timestamp without time zone DEFAULT LOCALTIMESTAMP,
    "firstName" character varying(80),
    "lastName" character varying(80),
    address character varying(100),
    suburb character varying(50),
    state character varying(10),
    "postCode" character varying(10),
    email character varying(100),
    "phoneNo" character varying(30),
    gender character varying(10),
    dob date,
    seq bigint default nextval('multisequencedmember_seq_seq')
);

ALTER SEQUENCE multisequencedmember_seq_seq OWNED BY "multiSequencedMember".seq;

CREATE VIEW "memberView" as select * from member;
 
