<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Pnrdetail extends CI_Controller {
    function __construct()
	{
		parent::__construct();
        $this->load->model("navitaire_model");
        $this->load->model("pnrdetail_model");
        $this->load->library("mongo_db");
	}

	function checkPNR($pnr_code) {
        try {
            $checkPNR = $this->pnrdetail_model->checkPNR($pnr_code);
            if(!empty($checkPNR)) {
                echo json_encode(array('fromLocal' => true));
            }
            else {
                echo json_encode(array('fromLocal' => false));
            }
        }
        catch (Exception $e) {
            echo json_encode(array("status" => 0, "message" => $e->getMessage()));
        }
    }

	function readFromLocal($pnr_code)
	{
		try {
		    $result = array();
		    $totalCost = 0;
		    $balanceDue = 0;
		    $listPassengerID = array();
            $bookingInfo = $this->pnrdetail_model->getOne(array('RecordLocator' => $pnr_code), 'Booking');
            if(!empty($bookingInfo)) {
                $listPassangerInfo = $this->pnrdetail_model->getByCondition(array('BookingID' => $bookingInfo['BookingID']), 'BookingPassenger');
                foreach ($listPassangerInfo as $key => $value) {
                    $totalCost += (float)$value['TotalCost'];
                    $balanceDue += (float)$value['BalanceDue'];
                    array_push($listPassengerID, $value['PassengerID']);
                }
                $result = array(
                    'BookingID'             => $bookingInfo['BookingID'],
                    'RecordLocator'         => $pnr_code,
                    'CurrencyCode'          => $bookingInfo['CurrencyCode'],
                    'PaxCount'              => count($listPassangerInfo),
                    'BookingInfo'           => array(
                        'BookingStatus'     => $bookingInfo['Status'],
                        'CreatedDate'       => $bookingInfo['CreatedDate'],
                        'ModifiedDate'      => $bookingInfo['ModifiedDate'],
                        'PriceStatus'       => $bookingInfo['PriceStatus'],
                        'OwningCarrierCode' => $bookingInfo['OwningCarrierCode'],
                    ),
                    'POS'                   => array(
                        'AgentCode'         => $bookingInfo['CreatedAgentCode'],
                        'OrganizationCode'  => $bookingInfo['CreatedOrganizationCode'],
                        'DomainCode'        => $bookingInfo['CreatedDomainCode'],
                        'LocationCode'      => $bookingInfo['CreatedLocationCode']
                    ),
                    'SourcePOS'             => array(
                        'AgentCode'         => $bookingInfo['SourceAgentCode'],
                        'OrganizationCode'  => $bookingInfo['SourceOrganizationCode'],
                        'DomainCode'        => $bookingInfo['SourceDomainCode'],
                        'LocationCode'      => $bookingInfo['SourceLocationCode'],
                    ),
                    'BookingSum'            => array(
                        'BalanceDue'        => $balanceDue,
                        'TotalCost'         => $totalCost
                    ),
                    'ReceivedBy'            => array(
                        'ReceivedBy'        => $bookingInfo['ReceivedBy']
                    ),
                    'listPassengerID'       => $listPassengerID
                );
            }
			echo json_encode(array("status" => 1, "message" => "", "data" => $result));
		} catch (Exception $e) {
			echo json_encode(array("status" => 0, "message" => $e->getMessage()));
		}
	}

	function detail($pnr_code) {
        try {
            $result = $this->mongo_db->where(array('RecordLocator' => $pnr_code))->getOne('BookingCache');
            echo json_encode(array("status" => 1, "message" => "", "data" => $result));
        }
        catch (Exception $e) {
            echo json_encode(array("status" => 0, "message" => $e->getMessage()));
        }
    }

    function getFlightInfo() {
        try {
            $request = json_decode($this->input->get("q"), TRUE);
            $result = array();
            $passengerInfo = $this->pnrdetail_model->getOne(array('BookingID' => $request['BookingID']), 'BookingPassenger');
            $journeyLegInfo = $this->pnrdetail_model->getByConditionSort(array('PassengerID' => $passengerInfo['PassengerID']), array('DepartureDate' => 'asc'), 'PassengerJourneyLeg');
            if(!empty($journeyLegInfo)) {
                $legInfoList = array();
                foreach ($journeyLegInfo as $key => $value) {
                    $journeySegmentInfo = $this->pnrdetail_model->getOne(array('SegmentID' => $value['SegmentID']), 'PassengerJourneySegment');
                    $journey = array(
                        'ArrivalStation'        => $value['ArrivalStation'],
                        'DepartureStation'      => $value['DepartureStation'],
                        'STA'                   => $value['LegSTA'],
                        'STD'                   => $value['LegSTD'],
                        'FlightDesignator'      => array(
                            'CarrierCode'       => $value['CarrierCode'],
                            'FlightNumber'      => $value['FlightNumber']
                        ),
                        'LegNumber'             => $value['LegNumber'],
                        'LiftStatus'            => (!empty($value['LiftStatus'])) ? $value['LiftStatus'] : '',
                        'UnitDesignator'        => (!empty($value['UnitDesignator'])) ? $value['UnitDesignator'] : ''
                    );
                    array_push($legInfoList, $journey);
                }
                $result = $legInfoList;
            }
            echo json_encode(array("status" => 1, "message" => "", "data" => $result));
        }
        catch (Exception $e) {
            echo json_encode(array("status" => 0, "message" => $e->getMessage()));
        }
    }

    function getPassengerInfo() {
        try {
            $request = json_decode($this->input->get("q"), TRUE);
            $passengerInfo = $this->pnrdetail_model->getByConditionSort(array('PassengerID' => array('$in' => $request['PassengerID'])), array('LastName' => 'asc', 'FirstName' => 'asc'), 'BookingPassenger');
            if(!empty($passengerInfo)) {
                $passengerInfoList = array();
                foreach ($passengerInfo as $key => $value) {
                    $customerID = '';
                    $legInfo = $this->pnrdetail_model->getByConditionSort(array('PassengerID' => $value['PassengerID']), array('DepartureDate' => 'asc'), 'PassengerJourneyLeg');
                    $listSeat = array();
                    $passengerFee = array();
                    foreach ($legInfo as $leg) {
                        if(!empty($leg['UnitDesignator'])) {
                            array_push($listSeat, '<b style="font-weight: 900;">' . $leg['UnitDesignator'] . '</b>' . ' (' . $leg['DepartureStation'] . ' - ' . $leg['ArrivalStation'] . ')');
                        }
                        $SSRsInfoByPassenger = $this->pnrdetail_model->getByConditionSort(array('SegmentID' => $leg['SegmentID']), array('CreatedDate' => 'asc'), 'PassengerJourneySSR');
                        if(!empty($SSRsInfoByPassenger)) {
                            array_push($passengerFee, array(
                                'CarrierCode'       => $leg['CarrierCode'],
                                'FlightNumber'      => $leg['FlightNumber'],
                                'DepartureStation'  => $leg['DepartureStation'],
                                'ArrivalStation'    => $leg['ArrivalStation'],
                                'ssrs'              => $SSRsInfoByPassenger
                            ));
                        }
                    }

                    if(!empty($value['CustomerNumber'])) {
                        $customerInfo = $this->pnrdetail_model->getOne(array('CustomerNumber' => (int)$value['CustomerNumber']), set_sub_collection('Customer'));
                        if(!empty($customerInfo)) {
                            $customerID = $customerInfo['id'];
                        }
                    }

                    $passenger = array(
                        'CustomerNumber'        => (int)$value['CustomerNumber'],
                        'Names'                 => array(
                            'BookingName'       => array(
                                'FirstName'     => $value['FirstName'],
                                'MiddleName'    => $value['MiddleName'],
                                'LastName'      => $value['LastName']
                            )
                        ),
                        'PassengerInfo'         => array(
                            'BalanceDue'        => (float)$value['BalanceDue'],
                            'TotalCost'         => (float)$value['TotalCost']
                        ),
                        'PassengerFee'          => $passengerFee,
                        'PaxType'               => $value['PaxType'],
                        'listSeat'              => $listSeat,
                        'customerID'            => $customerID
                    );
                    array_push($passengerInfoList, $passenger);
                }
                $result = $passengerInfoList;
            }
            echo json_encode(array("status" => 1, "message" => "", "data" => $result));
        }
        catch (Exception $e) {
            echo json_encode(array("status" => 0, "message" => $e->getMessage()));
        }
    }

    function getContactInfo() {
        try {
            $request = json_decode($this->input->get("q"), TRUE);
            $contactInfo = $this->pnrdetail_model->getByCondition(array('BookingID' => $request['BookingID']), 'BookingContact');
            if(!empty($contactInfo)) {
                $contactInfoList = array();
                foreach ($contactInfo as $key => $value) {
                    $contact = array(
                        'TypeCode'              => $value['TypeCode'],
                        'Names'                 => array(
                            'BookingName'       => array(
                                'FirstName'     => $value['FirstName'],
                                'MiddleName'    => $value['MiddleName'],
                                'LastName'      => $value['LastName'],
                            )
                        ),
                        'EmailAddress'          => (!empty($value['EmailAddress'])) ? $value['EmailAddress'] : '',
                        'HomePhone'             => (!empty($value['HomePhone'])) ? $value['HomePhone'] : '',
                        'WorkPhone'             => (!empty($value['WorkPhone'])) ? $value['WorkPhone'] : '',
                        'OtherPhone'            => (!empty($value['OtherPhone'])) ? $value['OtherPhone'] : '',
                        'CompanyName'           => (!empty($value['CompanyName'])) ? $value['CompanyName'] : '',
                        'AddressLine1'          => (!empty($value['AddressLine1'])) ? $value['AddressLine1'] : '',
                        'AddressLine2'          => (!empty($value['AddressLine2'])) ? $value['AddressLine2'] : '',
                        'AddressLine3'          => (!empty($value['AddressLine3'])) ? $value['AddressLine3'] : '',
                        'City'                  => (!empty($value['City'])) ? $value['City'] : '',
                        'ProvinceState'         => (!empty($value['ProvinceState'])) ? $value['ProvinceState'] : '',
                        'PostalCode'            => (!empty($value['PostalCode'])) ? $value['PostalCode'] : '',
                        'SourceOrganization'    => (!empty($value['SourceOrganization'])) ? $value['SourceOrganization'] : '',
                        'CountryCode'           => (!empty($value['CountryCode'])) ? $value['CountryCode'] : ''
                    );
                    array_push($contactInfoList, $contact);
                }
                $result = $contactInfoList;
            }
            echo json_encode(array("status" => 1, "message" => "", "data" => $result));
        }
        catch (Exception $e) {
            echo json_encode(array("status" => 0, "message" => $e->getMessage()));
        }
    }

    function readFromAPI($pnr_code)
    {
        try {
            $response = $this->navitaire_model->getBooking($pnr_code);
            echo json_encode(array("status" => 1, "message" => "", "data" => $response));
        } catch (Exception $e) {
            echo json_encode(array("status" => 0, "message" => $e->getMessage()));
        }
    }
}