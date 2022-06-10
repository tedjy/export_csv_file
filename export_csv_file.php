<?php  if (checkLevel() != OPERATOR) { ?>
<div class="optionsmenu_p">
	<div class="dropdown">
		<button class="exerotpbtn btn-defaultexer btn-sm ripple-effect ripple-white dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			<label id="countChecks"></label>
		</button>
		<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
			<a class="dropdown-item" href="#" data-toggle="modal" data-target="#confirmcorp_emails"><i class="far fa-envelope"></i> <?= Translator('Send_OTP_email');?></a>
			<form class="form-horizontal" method="post" enctype="multipart/form-data">
				<div class="input-icon">
					<i class="far fa-file-csv"></i>
					<input class="dropdown-item2" type="submit" name="CSVExport_Classic" value="<?= Translator('Export_to_CSV'); ?>" />
				</div>
				<div class="input-icon">
					<i class="far fa-file-spreadsheet"></i>
					<input class="dropdown-item2" type="submit" name="CSVExport_AzureMicrosoft" value="<?= Translator('Export_to_Azure'); ?>" />
				</div>
			</form>
			<a class="dropdown-item" href="#" data-toggle="modal" data-target="#confirmcorp_delete"><i class="far fa-trash-alt"></i> <?= Translator('Delete');?></a> 
		</div>
	</div>
</div>

<?php if (isset($_POST['CSVExport_Classic'])) {
	if (!empty($_POST['speUsrSelect'])) {
	// Make CSV header file
	$filename = 'TOTP_Users_'.date('Y-m-d-H:i:s').'.csv';
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Content-Type: application/vnd.ms-excel");
	header('Content-Transfer-Encoding: binary');
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($FullPath));
	ob_end_clean();

	// Create row into CSV file
	$header = array("Company Name", "User", "Email Address", "Last OTP User Login", "Last Origin (Radius Client)", "NFC Serial Number", "Date of Creation");

	$terminated = ';';
	$eol = "\n";

	$CSVFile = fopen("php://output", 'w');
	fputcsv($CSVFile, $header, $terminated);

	foreach($_POST['speUsrSelect'] as $speUsrSelect) {
		$UsersTokens = $db->prepare("SELECT * FROM otp_tokens WHERE token = ?");
		$UsersTokens->execute(array($speUsrSelect));
		$UserTokenInfos = $UsersTokens->fetch();
		$ExistUserToken = $UsersTokens->rowCount();

		if ($ExistUserToken == 0) {
			$Session->setFlash(Translator('No_user_has_been_found'), "close", "error");
			header('Location: /companies.php');
			exit();
		} else {
			// Get Company Name by User Token
			$Companies = $db->prepare("SELECT * FROM otp_companies WHERE corpid = ?");
			$Companies->execute(array($UserTokenInfos['corpid']));
			$UserCompany = $Companies->fetch();

			if ($Companies->rowCount() > 0) {
				
				$GetUserCorp = $UserCompany['name'];
				$nomclient	=	$UserCompany['folder'];
				$nomuser	=	$UserTokenInfos['login'];
				$filename 	= 	"/home/$nomclient/$nomuser/.exer_authenticator";

				if (file_exists($filename)) {
					$handle = fopen('/home/'.$nomclient.'/'.$nomuser.'/.exer_authenticator', "r");
					if ($handle) {
						// the e correspond to input checked
						if (($buffer = fgets($handle)) != "e") {
							$secretkey = rtrim($buffer);
							$UserOTPSecreteKey = $secretkey;
						}

						fclose($handle);
					} 
				} else {
					$UserOTPSecreteKey = "Unable to get user secret key";
				}
			} else {
				$GetUserCorp = "Unknown company name";
			}

			$lineData = array($GetUserCorp, $UserTokenInfos['login'], $UserTokenInfos['email'], $UserTokenInfos['otp_last_connected'], $UserTokenInfos['otp_firewall'], $UserTokenInfos['serialnumber_card'], $UserTokenInfos['created_at']);
			fputcsv($CSVFile, $lineData, $terminated);
		}
	}
	fclose($CSVFile);
	exit();
	}
}


// Export CSV file for azure microsoft
if(isset($_POST['CSVExport_AzureMicrosoft'])) {
	if(!empty($_POST['speUsrSelect'])) {
	// Make CSV header file
	$filename = 'Azure_MFA_OATH_Token_'.date('Y-m-d-H:i:s').'.csv';
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Content-Type: application/vnd.ms-excel");
	header('Content-Transfer-Encoding: binary');
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($FullPath));
	ob_end_clean();

	// Create row into CSV file
	$header = array('upn','serial number','secret key','time interval','manufacturer','model');

	$terminated = ',';
	$eol = "\n";

	$CSVFile = fopen("php://output", 'a');
	fputs($CSVFile, implode($terminated, $header).$eol);


	foreach($_POST['speUsrSelect'] as $speUsrSelect) {
		$UsersTokens = $db->prepare("SELECT * FROM otp_tokens WHERE token = ?");
		$UsersTokens->execute(array($speUsrSelect));
		$UserTokenInfos = $UsersTokens->fetch();
		$ExistUserToken = $UsersTokens->rowCount();

		if ($ExistUserToken == 0) {
			$Session->setFlash(Translator('No_user_has_been_found'), "close", "error");
			header('Location: /companies.php');
			exit();
		} else {
		// Get Company Name by User Token
			$Companies = $db->prepare("SELECT * FROM otp_companies WHERE corpid = ?");
			$Companies->execute(array($UserTokenInfos['corpid']));
			$UserCompany = $Companies->fetch();

			if ($Companies->rowCount() > 0) {

				$getManufacturer = "EXER";
				$getModel = "TOTP";
				$getTimeInterval = "30";
				$GetUserCorp = $UserCompany['name'];
				$nomclient	=	$UserCompany['folder'];
				$nomuser	=	$UserTokenInfos['login'];
				$filename 	= 	"/home/$nomclient/$nomuser/.exer_authenticator";

				if (file_exists($filename)) {
				$handle = fopen('/home/'.$nomclient.'/'.$nomuser.'/.exer_authenticator', "r");
					if ($handle) {
					// the e correspond to input checked
						if (($buffer = fgets($handle)) != "e") {
							$secretkey = rtrim($buffer);
							$UserOTPSecreteKey = $secretkey;
						}
						fclose($handle);
					}
				} else {
					$UserOTPSecreteKey = "Unable to get user secret key";
				}
			} else {
				$GetUserCorp = "Unknown company name";
			}

			if($UserTokenInfos['serialnumber_card']) {
			$lineData = array($UserTokenInfos['email'], $UserTokenInfos['serialnumber_card'],
			$UserOTPSecreteKey, $getTimeInterval , $getManufacturer, $getModel);
			fputcsv($CSVFile, $lineData, $terminated);
			} else {
			$serialNumber = substr($UserTokenInfos['token'], 0, 9);
			$lineData = array($UserTokenInfos['email'], $serialNumber,
			$UserOTPSecreteKey, $getTimeInterval , $getManufacturer, $getModel);
			fputcsv($CSVFile, $lineData, $terminated);
			}

		}
	}
	fclose($CSVFile);
	exit();
	}
}

?>