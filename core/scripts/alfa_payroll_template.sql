-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 22, 2019 at 09:40 PM
-- Server version: 5.7.28-0ubuntu0.18.04.4
-- PHP Version: 7.2.24-0ubuntu0.18.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `icehrmdb`
--

--
-- Dumping data for table `DeductionGroup`
--

INSERT INTO `DeductionGroup` (`id`, `name`, `description`) VALUES
(1, 'Regular On-site (รายเดือน)', 'พนักงานรายเดือน - ประจำออฟฟิส'),
(2, 'Freelancers Off-site (รายวัน)', 'พนักงานรายวัน - ออกหน้างาน'),
(3, 'Sales', 'พนักงานขาย'),
(4, 'Regular Off-site (รายเดือน)', 'พนักงานรายเดือน - ออกหน้างาน'),
(5, 'Freelancers On-site (รายวัน)', 'พนักงานรายงัน - ประจำออฟฟิส');

--
-- Dumping data for table `Deductions`
--

INSERT INTO `Deductions` (`id`, `name`, `componentType`, `component`, `payrollColumn`, `rangeAmounts`, `deduction_group`) VALUES
(2, 'Tax', '[]', '[]', 5, '[{\"lowerCondition\":\"No Lower Limit\",\"lowerLimit\":0,\"upperCondition\":\"No Upper Limit\",\"upperLimit\":0,\"amount\":\"X * 0.21\",\"id\":\"rangeAmounts_1\"}]', NULL),
(3, 'Monthly to Daily wage', '[]', '[]', NULL, '[{\"lowerCondition\":\"gte\",\"lowerLimit\":\"0\",\"upperCondition\":\"No Upper Limit\",\"upperLimit\":0,\"amount\":\"X / 30\",\"id\":\"rangeAmounts_1\"}]', NULL),
(4, 'Monthly to Hourly wage', '[]', '[]', NULL, '[{\"lowerCondition\":\"No Lower Limit\",\"lowerLimit\":0,\"upperCondition\":\"No Upper Limit\",\"upperLimit\":0,\"amount\":\"X / 30\",\"id\":\"rangeAmounts_1\"},{\"lowerCondition\":\"No Lower Limit\",\"lowerLimit\":0,\"upperCondition\":\"No Upper Limit\",\"upperLimit\":0,\"amount\":\"X / 8\",\"id\":\"rangeAmounts_2\"}]', NULL),
(5, 'Electricity usage (kWh) to cost', '[]', '[]', 8, '[{\"lowerCondition\":\"No Lower Limit\",\"lowerLimit\":0,\"upperCondition\":\"No Upper Limit\",\"upperLimit\":0,\"amount\":\"X * 5\",\"id\":\"rangeAmounts_1\"}]', NULL);

--
-- Dumping data for table `DeductionTypes`
--

INSERT INTO `DeductionTypes` (`id`, `name`, `description`) VALUES
(1, 'Early withdrawal/หักเบิกร่วงหน้า', 'An early withdrawal on the next payment period'),
(2, 'Guarantee/หักเงินสะสม', 'Guarantee deduction withheld until a fixed maximum'),
(3, 'Electricity/ค่าไฟ', 'Deduction for usage of electricity'),
(4, 'Social Security/ค่าประกันสังคม', 'Social security deduction withheld until a fixed maximum');

--
-- Dumping data for table `IncentiveTypes`
--

INSERT INTO `IncentiveTypes` (`id`, `name`, `description`) VALUES
(1, 'Out-of-town/พิเศษออกต่างจังหวัด', 'Incentive for heading out of town on a business trip'),
(2, 'Forklift container unload/พิเศษลงของ', 'Incentive rewarded for operating the forklift when unloading a container'),
(3, 'Second delivery trip/พิเศษส่งของรอบสอง', 'Incentive awarded for making a second delivery trip on one day');

--
-- Dumping data for table `PayrollColumns`
--

INSERT INTO `PayrollColumns` (`id`, `name`, `calculation_hook`, `salary_components`, `deductions`, `add_columns`, `sub_columns`, `colorder`, `editable`, `enabled`, `default_value`, `calculation_columns`, `calculation_function`, `deduction_group`) VALUES
(1, 'Monthly Wage', NULL, '[\"1\"]', '[]', '[]', '[]', 1, 'No', 'Yes', '0', '', '', 1),
(2, 'Hours worked', 'AttendanceUtil_getRegularWorkedHours', '[]', '[]', '[]', '[]', 10, 'No', 'Yes', '0', '', '', NULL),
(3, 'Hourly wage', NULL, '[\"1\"]', '[\"4\"]', '[]', '[]', 3, 'No', 'Yes', '0', '', '', 1),
(8, 'Electricity usage (KWh)', 'EmployeeElectricity_getElectricityUsage', '[]', '[]', '[]', '[]', 30, 'No', 'Yes', '0', '', '', NULL),
(9, 'Daily Wage', NULL, '[\"1\"]', '[\"3\"]', '[]', '[]', 2, 'No', 'Yes', '0', '', '', 1),
(10, 'Days worked', 'AttendanceUtil_getWorkedDays', '[]', '[]', '[]', '[]', 11, 'No', 'Yes', '0', '', '', NULL),
(11, 'Hours overtime', 'AttendanceUtil_getOverTimeWorkedHours', '[]', '[]', '[]', '[]', 12, 'Yes', 'Yes', '0', '', '', NULL),
(12, 'Total salary regular rate', NULL, '[]', '[]', '[]', '[]', 18, 'Yes', 'Yes', '0', '[{\"name\":\"WAGE\",\"column\":\"3\",\"id\":\"calculation_columns_1\"},{\"name\":\"WORKED\",\"column\":\"2\",\"id\":\"calculation_columns_2\"}]', 'WAGE * WORKED', NULL),
(13, 'Total salary overtime rate', NULL, '[]', '[]', '[]', '[]', 19, 'No', 'Yes', '0', '[{\"name\":\"OVERTIME\",\"column\":\"11\",\"id\":\"calculation_columns_1\"},{\"name\":\"WAGE\",\"column\":\"3\",\"id\":\"calculation_columns_2\"}]', 'WAGE * OVERTIME * 1.5', NULL),
(14, 'Gas compensation', NULL, '[\"2\"]', '[]', '[]', '[]', 20, 'Yes', 'Yes', '0', '', '', NULL),
(15, 'Vehicle usage depreciation compensation', NULL, '[\"9\"]', '[]', '[]', '[]', 21, 'Yes', 'Yes', '0', '', '', NULL),
(16, 'Telephone compensation', NULL, '[\"4\"]', '[]', '[]', '[]', 22, 'Yes', 'Yes', '0', '', '', NULL),
(17, 'Sanitary cleaning compensation', NULL, '[\"11\"]', '[]', '[]', '[]', 23, 'No', 'Yes', '0', '', '', NULL),
(18, 'Compensations total', NULL, '[]', '[]', '[\"14\",\"15\",\"16\",\"17\"]', '[]', 29, 'Yes', 'Yes', '0', '', '', NULL),
(19, 'Electricity usage deduction', NULL, '[]', '[\"5\"]', '[]', '[]', 31, 'Yes', 'Yes', '0', '', '', NULL),
(20, 'Water usage deduction', NULL, '[\"12\"]', '[]', '[]', '[]', 32, 'Yes', 'Yes', '0', '', '', NULL),
(21, 'Extra inhabitant deduction', NULL, '[\"13\"]', '[]', '[]', '[]', 33, 'Yes', 'Yes', '0', '', '', NULL),
(22, 'Sanitary cleaning deduction', NULL, '[\"14\"]', '[]', '[]', '[]', 34, 'Yes', 'Yes', '0', '', '', NULL),
(23, 'Early withdrawal deduction', 'EmployeeDeductions_getAdvancesTotal', '[]', '[]', '[]', '[]', 35, 'Yes', 'Yes', '0', '', '', NULL),
(24, 'Guarantee deduction', 'EmployeeDeductions_getGuaranteeTotal', '[\"15\"]', '[]', '[]', '[]', 36, 'No', 'Yes', '0', '', '', NULL),
(25, 'Deductions total', NULL, '[]', '[]', '[]', '[\"19\",\"20\",\"21\",\"22\",\"23\",\"24\"]', 49, 'Yes', 'Yes', '0', '', '', NULL),
(26, 'Out-of-town incentive', 'EmployeeIncentives_getOutOfTownTotal', '[]', '[]', '[]', '[]', 50, 'No', 'Yes', '0', '', '', NULL),
(27, 'Forklift contained unload incentive', 'EmployeeIncentives_getForkliftContainerTotal', '[]', '[]', '[]', '[]', 51, 'No', 'Yes', '0', '', '', NULL),
(28, 'Second delivery trip incentive', 'EmployeeIncentives_getSecondTripTotal', '[]', '[]', '[]', '[]', 52, 'Yes', 'Yes', '0', '', '', NULL),
(29, 'Pre-paid incentives total', 'EmployeeIncentives_getPrePaidTotal', '[]', '[]', '[]', '[]', 59, 'No', 'Yes', '0', '', '', NULL),
(30, 'Incentives total', NULL, '[]', '[]', '[\"27\",\"28\"]', '[\"26\"]', 60, 'Yes', 'Yes', '0', '', '', NULL),
(31, 'To be received', NULL, '[]', '[]', '[\"12\",\"13\",\"18\",\"30\",\"25\"]', '[]', 99, 'Yes', 'Yes', '0', '', '', NULL);

UPDATE `Settings` SET `value` = 'AlfaOvertimeCalculator' WHERE `Settings`.`name` = 'Attendance: Overtime Calculation Class';

COMMIT;
