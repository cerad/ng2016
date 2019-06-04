<?php
namespace AppBundle\Action\Project\Person\Admin\Sombero;

use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;
use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;
use AppBundle\Action\User\UserManager;
use AppBundle\Common\GuidGeneratorTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

use Doctrine\DBAL\Connection;
use Cerad\Bundle\AysoBundle\AysoFinder;

class ImportSomberoCommand extends Command
{
    private $userManager;
    private $regPersonConn;
    private $regPersonRepository;
    private $aysoFinder;
    private $phoneTransformer;

    public function __construct(
        UserManager $userManager,
        Connection  $regPersonConn,
        ProjectPersonRepositoryV2 $regPersonRepository,
        AysoFinder $aysoFinder,
        PhoneTransformer $phoneTransformer
    )
    {
        parent::__construct();

        $this->userManager = $userManager;

        $this->regPersonConn       = $regPersonConn;
        $this->regPersonRepository = $regPersonRepository;

        $this->aysoFinder = $aysoFinder;
        $this->phoneTransformer = $phoneTransformer;
    }
    protected function configure()
    {
        $this
            ->setName('import:sombero')
            ->setDescription('Import Sombero Registration Data');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = './var/sombero/Sombero20160419.xlsx';

        echo sprintf("Import Sombero Registration from %s\n",$filename);

        $this->import($filename);

        echo sprintf("Import Completed.\n");
    }
    private function import($filename)
    {
        /** @var Xlsx $reader */
        $reader = IOFactory::createReaderForFile($filename);
        $reader->setReadDataOnly(true);

        $wb = $reader->load($filename);
        $ws = $wb->getSheetByName('Report');

        $wsData = $ws->toArray();

        $header = array_shift($wsData);
        //var_dump($header);
        if (!$header) {
            return;
        }
        $count = 0;
        foreach($wsData as $row) {

            $processed = $this->importRow($row);

            if ($processed) {
                $count++;
            }
//            if (($count % 10) === 0) {
//                echo sprintf("\rProcessed %4d",$count);
//            }
        }
        //echo sprintf("\rProcessed %4d vols      \n",$count);
    }
    /* ============================================================
     * Format
     */
    private $lastNameIndex     =  0; // [ 0]=> "Account Last Name" Sadly, no full name
    private $firstNameIndex    =  1; // [ 1]=> "Account First Name"
    private $aysoidIndex       =  2; // [ 2]=> "AYSO ID"
    private $refereeBadgeIndex =  3; // [ 3]=> "Referee Level?" aka badge, did not see any national 1 or two
    private $datesAvailIndex   =  4; // [ 4]=> "Dates Available" free form
    private $timesAvailIndex   =  5; // [ 5]=> "Times Available" free form
//  private $groundTransIndex  =  6; // [ 6]=> "Ground Transportation" (standard phases)
//  private $stateIndex        = 11; // [11]=> "State" Michigan or CA
    private $emailIndex        = 12; // [12]=> "Users Email"
    private $phoneIndex        = 13; // [13]=> "Cell phone"
//  private $regDateIndex      = 14; // [14]=> "Registration Date" Can we set this?, not consistent
    private $shirtSizeIndex    = 17; // [17]=> "T-Shirt Size" AM,AL
//  private $sectionIndex      = 21; // [21]=> "Section"
//  private $areaIndex         = 22; // [22]=> "Area"
    private $regionIndex       = 23; // [23]=> "Region" 609
    private $safeHavenIndex    = 24; // [24]=> "Safe Haven Certified?" Yes/No
    private $concussionIndex   = 25; // [25]=> "CDC Concussion Awareness Certified?" Yes/No
//  private $verifiedIndex     = 29; // [29]=> "Verified in eAYSO?" Yes/Y
    private $notesIndex        = 33; // [33]=> string(5) "Notes" Looks like assignor notes

    private $names   = [];
    private $emails  = [];
    private $aysoIds = [];

    private $emailSpecial = [
        'regioncomish914@yahoo.com' => ['David Lunsford',    'Johnathan Lunsford', 'Mark Lunsford'],
        'jbernier136@yahoo.com'     => ['Joseph Bernier',    'Eric Bernier'],
        'bmacy@bak.rr.com'          => ['Brenda Fitzpatrick','Brigid Macy'],
        'bobby_csi@sbcglobal.net'   => ['Robert Orozco',     'Samantha Orozco'],
    ];
    private $projectId = 'AYSONationalOpenCup2018';

    private function importRow($row)
    {
        $aysoId = trim($row[$this->aysoidIndex]);
        $email  = strtolower(trim($row[$this->emailIndex]));
        $name   = $this->getPersonName($row);

        // Blank lines
        if (!$email) {
            return false; // Ignore blank lines
        }
        // Dup emails
        if (!isset($this->emails[$email])) {
            $this->emails[$email] = $row;
        }
        else {
            if (!isset($this->emailSpecial[$email])) {
                echo sprintf("*** Duplicate email %s %-32s %s\n", $aysoId, $name, $email);
            }
        }
        // Dup names
        if (!isset($this->names[$name])) {
            $this->names[$name] = $row;
        }
        else {
            echo sprintf("*** Duplicate name %s %-32s %s\n",$aysoId,$name,$email);
        }
        // Dup aysoIds
        if (!isset($this->aysoIds[$aysoId])) {
            $this->aysoIds[$aysoId] = $row;
        }
        else {
            echo sprintf("*** Duplicate aysoId %s %-32s %s\n",$aysoId,$name,$email);
        }
        // Volunteer record
        $vol = $this->aysoFinder->findVol($aysoId);
//        if (!$vol) {
//            echo sprintf("*** No ayso record for %s %-32s %s\n",$aysoId,$name,$email);
//        }
        $regYear = isset($vol['regYear']) ? $vol['regYear'] : null;

        $verified = $regYear >= 'MY2015' ? true : false;

        // Region
        $regionNumber = (integer)$row[$this->regionIndex];
        $orgKey = $regionNumber ? sprintf('AYSOR:%04d',$regionNumber) : null;

        // Phone
        $phone = $this->phoneTransformer->reverseTransform($row[$this->phoneIndex]);

        // Shirt size
        $shirtSizes = [
            'YS'   => 'youths',
            'YM'   => 'youthm',
            'YL'   => 'youthl',
            'AS'   => 'adults',
            'AM'   => 'adultm',
            'AL'   => 'adultl',
            'AXL'  => 'adultlx',
            'A2XL' => 'adultlxx',
            'A3XL' => 'adultlxxx',
            'A4XL' => 'adultlxxxx',
        ];
        $shirtSize = $shirtSizes[$row[$this->shirtSizeIndex]];

        // Plans
        $plans = [
            'willReferee' => 'Yes',
            'fromSombero' => 'Yes',
        ];
        // User note aka availability
        $datesAvail = trim($row[$this->datesAvailIndex]);
        $timesAvail = trim($row[$this->timesAvailIndex]);
        $notesUser  = $datesAvail . "\n" . $timesAvail;

        // No dob, age or gender info
        $dob = null;
        $age = null;
        $gender = null;

        // Create or get the user
        $personId = $this->importUser($email,$name);

        // Did we already process?
        $sql = 'SELECT id FROM projectPersons WHERE projectKey = ? AND personKey = ?';
        $stmt = $this->regPersonConn->executeQuery($sql,[$this->projectId,$personId]);
        if ($stmt->fetch()) {
            return true;
        }
        // Have a previous record?
        $sql = 'SELECT age,dob,gender FROM projectPersons WHERE projectKey = \'AYSONationalGames2014\' AND personKey = ?';
        $stmt = $this->regPersonConn->executeQuery($sql,[$personId]);
        $regPerson = $stmt->fetch();
        if ($regPerson) {

            // Age, gender and dob are the only things that carry
            $age = (integer)$regPerson['age'];
            $age = $age ? $age + 2 : null;
            $dob = $regPerson['dob'];

            $gender = $regPerson['gender'];
        }
        $regPerson = [
            'projectKey' => $this->projectId,
            'personKey'  => $personId,
            'orgKey'     => $orgKey,
            'fedKey'     => 'AYSOV:' . $aysoId,
            'regYear'    => $regYear,
            'registered' => null, // Login flag
            'verified'   => $verified,
            'name'       => $this->regPersonRepository->generateUniqueName($this->projectId,$name),
            'email'      => $email,
            'phone'      => $phone,
            'gender'     => $gender,
            'dob'        => $dob,
            'age'        => $age,
            'shirtSize'  => $shirtSize,
            'notes'      => trim($row[$this->notesIndex]),
            'notesUser'  => $notesUser,
            'plans'      => serialize($plans),
            'avail'      => null,
        ];
        $this->regPersonConn->insert('projectPersons',$regPerson);
        $regPersonId = $this->regPersonConn->lastInsertId();

        $this->addRolesAndCerts($row,$vol,$regPersonId);

        //var_dump($regPerson);
        // echo sprintf("Inserted %d\n",$regPersonId); die();

        return true;
    }
    private function addRolesAndCerts($row,$vol,$regPersonId)
    {
        $roleVerified = true;
        
        $fedId = isset($vol['fedKey']) ? $vol['fedKey'] : null;

        $certTemplate = [
            'projectPersonId' => $regPersonId,
            'role'      => null,
            'roleDate'  => null,
            'badge'     => null,
            'badgeUser' => null,
            'badgeDate' => null,
            'active'    => false,
            'verified'  => false,
        ];
        // Referee Cert
        $cert = $certTemplate;
        $refereeBadge = trim($row[$this->refereeBadgeIndex]);
        $certAyso = $this->aysoFinder->findVolCert($fedId,'CERT_REFEREE');
        if ($certAyso) {
            unset($certAyso['fedKey']);
            $cert = array_replace($cert,$certAyso);
            $cert['badgeUser'] = $certAyso['badge'];
            $cert['verified']  = true;
        }
        else {
            $cert['role']     = 'CERT_REFEREE';
            $cert['badge']    = $refereeBadge;
            $cert['verified'] = false;

            $roleVerified = false;
        }
        $cert['badgeUser'] = $cert['badgeUser'] === $refereeBadge ?  $cert['badgeUser'] : $refereeBadge;
        $this->regPersonConn->insert('projectPersonRoles',$cert);

        // Safe Haven Cert
        $cert = $certTemplate;
        $certAyso = $this->aysoFinder->findVolCert($fedId,'CERT_SAFE_HAVEN');
        if ($certAyso) {
            unset($certAyso['fedKey']);
            $cert = array_replace($cert,$certAyso);
            $cert['verified'] = true;
        }
        else {
            $cert['role']     = 'CERT_SAFE_HAVEN';
            $cert['verified'] = false;

            $badge = strtolower(trim($row[$this->safeHavenIndex]));
            if ($badge === 'y') {
                $cert['badge'] = 'AYSO';
            }
            $roleVerified = false;
        }
        $this->regPersonConn->insert('projectPersonRoles',$cert);

        // Concussion Cert
        $cert = $certTemplate;
        $certAyso = $this->aysoFinder->findVolCert($fedId,'CERT_CONCUSSION');
        if ($certAyso) {
            unset($certAyso['fedKey']);
            $cert = array_replace($cert,$certAyso);
            $cert['verified'] = true;
        }
        else {
            $cert['role']     = 'CERT_CONCUSSION';
            $cert['verified'] = false;

            $badge = strtolower(trim($row[$this->concussionIndex]));
            if ($badge === 'y') {
                $cert['badge'] = 'CDC Concussion';
            }
            $roleVerified = false;
        }
        $this->regPersonConn->insert('projectPersonRoles',$cert);

        // And the role
        $role = [
            'projectPersonId' => $regPersonId,
            'role'     => 'ROLE_REFEREE',
            'active'   => true,
            'verified' => $roleVerified,
        ];
        $this->regPersonConn->insert('projectPersonRoles',$role);
    }
    private function getPersonName($row)
    {
        $firstName = ucfirst(trim($row[$this->firstNameIndex]));
        $lastName  = ucfirst(trim($row[$this->lastNameIndex]));
        return $firstName . ' ' . $lastName;
    }
    use GuidGeneratorTrait;
    private function importUser($email,$name)
    {
        $user = $this->userManager->findUser($email);

        if ($user) {
            $personId = $user['personId'];
            //echo sprintf("Existing user: =%s= =%s= %s\n",$user['name'],$name,$email);
            if (!isset($this->emailSpecial[$email])) {
                return $personId;
            }
            $nameSpecial = $this->emailSpecial[$email][0];
            if ($name === $nameSpecial) {
                return $personId;
            }
            // Only one case Eric Bernier
            return $this->generateGuid();
            //echo sprintf("*** Existing user for special %s %s\n",$email,$name);
        }
        // Make a user
        $user = $this->userManager->createUser($email,$name);
        $user = $this->userManager->saveUser($user);

        return $user['personId'];
    }
}