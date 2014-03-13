<?php 
include('../lib/aemdb.php');
include('../lib/functions.php'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>AEM - Blackout Config</title>
<?php htmlHead(); ?>
<script language="javascript">
  function setStartTimeDisabled( disabledState )
    {
    setDisabled('startDateHourSelect', disabledState);
    setDisabled('startDateMinuteSelect', disabledState);
    setDisabled('startDateTzSelect', disabledState);
    }
function setDisabled( elementId, disabledState )
    {
    var element = document.getElementById( elementId );
    if (element)
      {
      element.disabled = disabledState;
      }
    }
</script>
</head>

<body>
<?php topOpg();  
$mode=$_POST['mode'];
if($mode == 'IMMEDIATE'){
	$cron['min'] = date("i");
	$cron['hour'] = date("H");
	$cron['dom'] = "*";
	$cron['month'] = "*";
	$cron['dow'] = "*";
}else{
	$cron['min'] = $_POST['startMinute'];
	$cron['hour'] = $_POST['startHour'];
	if($mode == "ONE_TIME"){
		$cron['dom'] = $_POST['OstartDay'];
		$cron['month'] = $_POST['OstartMonth']+1;
		$cron['dow'] = "*";		
	}
	if($mode == "WEEKLY"){
		$cron['dom'] = "*";
		$cron['month'] = "*";
		$cron['dow'] = implode(",",$_POST['selectedWeekdays']);		
	}
	if($mode == "MONTHLY"){
		$cron['dom'] = $_POST['MstartDay'];
		$cron['month'] = "*";
		$cron['dow'] = "*";
	}
	if($mode == "YEARLY"){
		$cron['dom'] = $_POST['YstartDay'];
		$cron['month'] = $_POST['YstartMonth']+1;
		$cron['dow'] = "*";
	}
}
$cronstr=$cron['min']." ".$cron['hour']." ".$cron['dom']." ".$cron['month']." ".$cron['dow'];
$duration = $_POST['durationDays']*86400+$_POST['durationHours']*3600+$_POST['durationMins']*60;
?>
<form method="POST" action="<?php echo 'blackoutcfg.php?step='.$_GET['step'].'&action=postblackout'; ?>">
<!--<table width="500">
  <tbody>
    <tr>
      <td colspan="3">You may optionally add comments describing   the reason for the blackout. </td>
    </tr>
    <tr>
      <td><input name="comments" maxlength="150" size="80" /></td>
    </tr>
  </tbody>
</table>-->
<h3>Blackout Schedules </h3><br />
<input name="step" value="<?php print $_GET['step']; ?>" type="hidden" />
<input name="sequence" value="<?php print $_GET['sequence']; ?>" type="hidden" />
<table width="750" style="text-align:left;" cellspacing="5">
  <tbody>
    <tr>
      <td><img src="images/1ptTrans.gif" alt="" width="40" height="5" /></td>
      <td width="10%" nowrap="nowrap">Start Time: </td>
      <td><select id="startDateHourSelect" disabled="disabled" size="1" name="startHour">
        <option value="0">12 AM</option>
        <option value="1">1   AM</option>
        <option value="2">2 AM</option>
        <option value="3">3 AM</option>
        <option value="4">4 AM</option>
        <option value="5">5 AM</option>
        <option value="6">6   AM</option>
        <option value="7">7 AM</option>
        <option value="8">8 AM</option>
        <option value="9">9 AM</option>
        <option value="10">10 AM</option>
        <option value="11">11   AM</option>
        <option value="12">12 PM</option>
        <option value="13">1 PM</option>
        <option selected="selected" value="14">2 PM</option>
        <option value="15">3 PM</option>
        <option value="16">4 PM</option>
        <option value="17">5 PM</option>
        <option value="18">6   PM</option>
        <option value="19">7 PM</option>
        <option value="20">8 PM</option>
        <option value="21">9 PM</option>
        <option value="22">10 PM</option>
        <option value="23">11 PM</option>
      </select>
        :
        <select id="startDateMinuteSelect"  disabled="disabled" size="1" name="startMinute">
          <option value="0">00</option>
          <option value="1">01</option>
          <option value="2">02</option>
          <option value="3">03</option>
          <option value="4">04</option>
          <option value="5">05</option>
          <option value="6">06</option>
          <option value="7">07</option>
          <option value="8">08</option>
          <option value="9">09</option>
          <option value="10">10</option>
          <option value="11">11</option>
          <option value="12">12</option>
          <option value="13">13</option>
          <option value="14">14</option>
          <option value="15">15</option>
          <option value="16">16</option>
          <option value="17">17</option>
          <option value="18">18</option>
          <option value="19">19</option>
          <option value="20">20</option>
          <option value="21">21</option>
          <option value="22">22</option>
          <option value="23">23</option>
          <option value="24">24</option>
          <option value="25">25</option>
          <option value="26">26</option>
          <option value="27">27</option>
          <option value="28">28</option>
          <option value="29">29</option>
          <option value="30">30</option>
          <option value="31">31</option>
          <option value="32">32</option>
          <option value="33">33</option>
          <option value="34">34</option>
          <option selected="selected" value="35">35</option>
          <option value="36">36</option>
          <option value="37">37</option>
          <option value="38">38</option>
          <option value="39">39</option>
          <option value="40">40</option>
          <option value="41">41</option>
          <option value="42">42</option>
          <option value="43">43</option>
          <option value="44">44</option>
          <option value="45">45</option>
          <option value="46">46</option>
          <option value="47">47</option>
          <option value="48">48</option>
          <option value="49">49</option>
          <option value="50">50</option>
          <option value="51">51</option>
          <option value="52">52</option>
          <option value="53">53</option>
          <option value="54">54</option>
          <option value="55">55</option>
          <option value="56">56</option>
          <option value="57">57</option>
          <option value="58">58</option>
          <option value="59">59</option>
        </select>
        <select id="startDateTzSelect" disabled="disabled" size="1" name="timeZoneID">
          <option value="">Please select...</option>
          <option value="America/Los_Angeles">(GMT -08:00) Pacific Time (US &amp; Canada);   Tijuana</option>
          <option value="America/Phoenix">(GMT -07:00) Arizona</option>
          <option value="America/Denver">(GMT -07:00) Mountain Time (US &amp;   Canada)</option>
          <option value="America/Costa_Rica">(GMT -06:00) Central   America</option>
          <option value="America/Mexico_City">(GMT -06:00) Mexico   City</option>
          <option value="America/Regina">(GMT -06:00) Saskatchewan</option>
          <option value="America/Chicago">(GMT -06:00) Central Time (US &amp;   Canada)</option>
          <option value="America/Bogota">(GMT -05:00) Bogota, Lima,   Quito</option>
          <option selected="selected" value="America/New_York">(GMT -05:00) Eastern Time   (US &amp; Canada)</option>
          <option value="America/Indianapolis">(GMT -05:00)   Indiana (East)</option>
          <option value="America/Caracas">(GMT -04:00) Caracas, La   Paz</option>
          <option value="America/Halifax">(GMT -04:00) Atlantic Time   (Canada)</option>
          <option value="America/Santiago">(GMT -04:00) Santiago</option>
          <option value="America/Puerto_Rico">(GMT -04:00) Puerto Rico</option>
          <option value="America/Buenos_Aires">(GMT -03:00) Buenos Aires, Georgetown</option>
          <option value="America/Godthab">(GMT -03:00) Greenland</option>
          <option value="America/Sao_Paulo">(GMT -03:00) Brasilia</option>
          <option value="America/Noronha">(GMT -02:00) Fernando de Noronha, Atlantic   islands</option>
          <option value="Atlantic/Azores">(GMT -01:00) Azores</option>
          <option value="Atlantic/Cape_Verde">(GMT -01:00) Cape Verde Is.</option>
          <option value="Africa/Casablanca">(GMT) Casablanca, Monrovia</option>
          <option value="Europe/London">(GMT) Dublin, Edinburgh, Lisbon, London</option>
          <option value="UTC">(UTC) Coordinated Universal Time</option>
          <option value="Africa/Algiers">(GMT +01:00) West Central Africa</option>
          <option value="Europe/Paris">(GMT +01:00) Brussels, Copenhagen, Madrid, Paris</option>
          <option value="Europe/Amsterdam">(GMT +01:00) Amsterdam, Berlin, Bern, Rome,   Stockholm, Vienna</option>
          <option value="Europe/Belgrade">(GMT +01:00) Belgrade,   Bratislava, Budapest, Ljubljana, Prague</option>
          <option value="Europe/Warsaw">(GMT +01:00) Sarajevo, Skopje, Sofija, Vilnius, Warsaw,   Zagreb</option>
          <option value="Europe/Bucharest">(GMT +02:00) Cairo,   Bucharest</option>
          <option value="Asia/Jerusalem">(GMT +02:00) Jerusalem</option>
          <option value="Africa/Harare">(GMT +02:00) Harare, Pretoria</option>
          <option value="Europe/Helsinki">(GMT +02:00) Helsinki, Riga, Tallinn</option>
          <option value="Asia/Baghdad">(GMT +03:00) Baghdad</option>
          <option value="Asia/Kuwait">(GMT   +03:00) Kuwait, Riyadh</option>
          <option value="Africa/Nairobi">(GMT +03:00)   Nairobi</option>
          <option value="Europe/Moscow">(GMT +03:00) Moscow, St.   Petersburg, Volgograd</option>
          <option value="Asia/Muscat">(GMT +04:00) Abu Dhabi,   Muscat</option>
          <option value="Asia/Yerevan">(GMT +04:00) Baku, Tbilisi,   Yerevan</option>
          <option value="Asia/Yekaterinburg">(GMT +05:00)   Ekaterinburg</option>
          <option value="Asia/Karachi">(GMT +05:00) Islamabad,   Karachi, Tashkent</option>
          <option value="Asia/Calcutta">(GMT +05:30) Chennai,   Kolkata, Mumbai, New Delhi, Pune</option>
          <option value="Asia/Almaty">(GMT +06:00)   Almaty, Novosibirsk</option>
          <option value="Asia/Colombo">(GMT +06:00) Sri   Jayawardenepura</option>
          <option value="Asia/Dacca">(GMT +06:00) Astana,   Dhaka</option>
          <option value="Asia/Krasnoyarsk">(GMT +07:00) Krasnoyarsk</option>
          <option value="Asia/Bangkok">(GMT +07:00) Bangkok, Hanoi, Jakarta</option>
          <option value="Asia/Irkutsk">(GMT +08:00) Irkutsk, Ulaan Bataar</option>
          <option value="Asia/Kuala_Lumpur">(GMT +08:00) Kuala Lumpur, Singapore</option>
          <option value="Asia/Taipei">(GMT +08:00) Taipei</option>
          <option value="Australia/Perth">(GMT +08:00) Perth</option>
          <option value="Asia/Hong_Kong">(GMT +08:00) Hong Kong</option>
          <option value="Asia/Shanghai">(GMT +08:00) Beijing, Chongqing, Urumqi</option>
          <option value="Asia/Seoul">(GMT +09:00) Seoul</option>
          <option value="Asia/Yakutsk">(GMT   +09:00) Yakutsk</option>
          <option value="Asia/Tokyo">(GMT +09:00) Osaka, Sapporo,   Tokyo</option>
          <option value="Australia/Adelaide">(GMT +09:30) Adelaide</option>
          <option value="Australia/Sydney">(GMT +10:00) Canberra, Melbourne, Sydney</option>
          <option value="Asia/Vladivostok">(GMT +10:00) Vladivostok</option>
          <option value="Australia/Brisbane">(GMT +10:00) Brisbane</option>
          <option value="Australia/Hobart">(GMT +10:00) Hobart</option>
          <option value="Pacific/Guam">(GMT +10:00) Guam, Port Moresby</option>
          <option value="Pacific/Guadalcanal">(GMT +11:00) Magadan, Solomon Is., New   Caledonia</option>
          <option value="Pacific/Auckland">(GMT +12:00) Auckland,   Wellington</option>
          <option value="Pacific/Fiji">(GMT +12:00) Fiji, Kamchatka,   Marshall Is.</option>
          <option value="Pacific/Tongatapu">(GMT +13:00)   Nuku'alofa</option>
          <option value="Pacific/Apia">(GMT -11:00) Midway Island,   Samoa</option>
          <option value="Pacific/Honolulu">(GMT -10:00) Hawaii</option>
          <option value="America/Anchorage">(GMT -09:00) Alaska</option>
        </select></td>
    </tr>
    <tr>
      <td></td>
      <td width="10%" nowrap="nowrap">Duration: </td>
      <td></td>
    </tr>
    <tr>
      <td></td>
      <td> Days: </td>
      <td><select size="1" name="durationDays">
        <option selected="selected" value="0">0</option>
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>
        <option value="11">11</option>
        <option value="12">12</option>
        <option value="13">13</option>
        <option value="14">14</option>
        <option value="15">15</option>
        <option value="16">16</option>
        <option value="17">17</option>
        <option value="18">18</option>
        <option value="19">19</option>
        <option value="20">20</option>
        <option value="21">21</option>
        <option value="22">22</option>
        <option value="23">23</option>
        <option value="24">24</option>
        <option value="25">25</option>
        <option value="26">26</option>
        <option value="27">27</option>
        <option value="28">28</option>
        <option value="29">29</option>
        <option value="30">30</option>
      </select></td>
    </tr>
    <tr>
      <td></td>
      <td> Hours: </td>
      <td><select size="1" name="durationHours">
        <option selected="selected" value="0">0</option>
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>
        <option value="11">11</option>
        <option value="12">12</option>
        <option value="13">13</option>
        <option value="14">14</option>
        <option value="15">15</option>
        <option value="16">16</option>
        <option value="17">17</option>
        <option value="18">18</option>
        <option value="19">19</option>
        <option value="20">20</option>
        <option value="21">21</option>
        <option value="22">22</option>
        <option value="23">23</option>
      </select></td>
    </tr>
    <tr>
      <td></td>
      <td> Minutes: </td>
      <td><select size="1" name="durationMins">
        <option selected="selected" value="0">0</option>
        <option value="15">15</option>
        <option value="30">30</option>
        <option value="45">45</option>
      </select></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td colspan="3"><input id="immediateRadio" onclick="setStartTimeDisabled(true);" name="mode" value="IMMEDIATE" checked="checked" type="radio" />
        <strong>Immediate</strong> <br /></td>
    </tr>
    <tr>
      <td colspan="3"><input onclick="setStartTimeDisabled(false);" name="mode" value="ONE_TIME" type="radio" />
        <strong>One   Time</strong> <br /></td>
    </tr>
    <tr>
      <td><img src="images/1ptTrans.gif" alt="" width="40" height="5" /></td>
      <td width="10%" nowrap="nowrap">Date: </td>
      <td><select id="startDateMonthSelect" size="1" name="OstartMonth">
        <option value="0">January</option>
        <option value="1">February</option>
        <option value="2">March</option>
        <option value="3">April</option>
        <option value="4">May</option>
        <option value="5">June</option>
        <option selected="selected" value="6">July</option>
        <option value="7">August</option>
        <option value="8">September</option>
        <option value="9">October</option>
        <option value="10">November</option>
        <option value="11">December</option>
      </select>
        <select id="startDateDayOfMonthSelect" size="1" name="OstartDay">
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option selected="selected" value="6">6</option>
          <option value="7">7</option>
          <option value="8">8</option>
          <option value="9">9</option>
          <option value="10">10</option>
          <option value="11">11</option>
          <option value="12">12</option>
          <option value="13">13</option>
          <option value="14">14</option>
          <option value="15">15</option>
          <option value="16">16</option>
          <option value="17">17</option>
          <option value="18">18</option>
          <option value="19">19</option>
          <option value="20">20</option>
          <option value="21">21</option>
          <option value="22">22</option>
          <option value="23">23</option>
          <option value="24">24</option>
          <option value="25">25</option>
          <option value="26">26</option>
          <option value="27">27</option>
          <option value="28">28</option>
          <option value="29">29</option>
          <option value="30">30</option>
          <option value="31">31</option>
        </select>
        <select id="startDateYearSelect" size="1" name="startYear">
          <option selected="selected" value="2011">2011</option>
          <option value="2012">2012</option>
          <option value="2013">2013</option>
          <option value="2014">2014</option>
          <option value="2015">2015</option>
          <option value="2016">2016</option>
          <option value="2017">2017</option>
        </select>
        <!--    Copyright 2004-2006 BMC Software Inc.  --></td>
    </tr>
    <tr>
      <td colspan="3"><input id="weeklyRadio" onclick="setStartTimeDisabled(false);" name="mode" value="WEEKLY" type="radio" />
      <strong>Weekly - repeats every week</strong> <br /></td>
    </tr>
    <tr>
      <td><img src="images/1ptTrans.gif" alt="" width="40" height="5" /></td>
      <td width="10%" nowrap="nowrap">Days Blackout Occurs: </td>
      <td nowrap="nowrap"><table border="0" width="80%">
        <tbody>
          <tr>
            <td><input name="selectedWeekdays[]" value="1" type="checkbox" />
              Mon, </td>
            <td><input name="selectedWeekdays[]" value="2" type="checkbox" />
              Tue, </td>
            <td><input name="selectedWeekdays[]" value="3" type="checkbox" />
              Wed, </td>
            <td><input name="selectedWeekdays[]" value="4" type="checkbox" />
              Thu, </td>
            <td><input name="selectedWeekdays[]" value="5" type="checkbox" />
              Fri, </td>
            <td><input name="selectedWeekdays[]" value="6" type="checkbox" />
              Sat, </td>
            <td><input name="selectedWeekdays[]" value="0" type="checkbox" />
              Sun </td>
          </tr>
        </tbody>
      </table></td>
    </tr>
    <tr>
      <td colspan="3"><input id="monthlyRadio" onclick="setStartTimeDisabled(false);" name="mode" value="MONTHLY" type="radio" />
        <strong>Monthly - repeats every month</strong> <br /></td>
    </tr>
 <!--   <tr>
      <td><img src="images/1ptTrans.gif" alt="" width="40" height="5" /></td>
      <td width="10%" nowrap="nowrap">Starting: </td>
      <td><select id="monthlyMonthSelect" size="1" name="startMonth">
        <option value="0">January</option>
        <option value="1">February</option>
        <option value="2">March</option>
        <option value="3">April</option>
        <option value="4">May</option>
        <option value="5">June</option>
        <option selected="selected" value="6">July</option>
        <option value="7">August</option>
        <option value="8">September</option>
        <option value="9">October</option>
        <option value="10">November</option>
        <option value="11">December</option>
      </select></td>
    </tr> -->
    <tr>
      <td><img src="images/1ptTrans.gif" alt="" width="40" height="5" /></td>
      <td width="10%" nowrap="nowrap"><input onclick="selectRadio('monthlyRadio')" name="subMode" value="MONTHLY_BASIC" type="radio" />
        Day of the month: </td>
      <td><select id="monthlyDayOfMonthSelect" size="1" name="MstartDay">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option selected="selected" value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>
        <option value="11">11</option>
        <option value="12">12</option>
        <option value="13">13</option>
        <option value="14">14</option>
        <option value="15">15</option>
        <option value="16">16</option>
        <option value="17">17</option>
        <option value="18">18</option>
        <option value="19">19</option>
        <option value="20">20</option>
        <option value="21">21</option>
        <option value="22">22</option>
        <option value="23">23</option>
        <option value="24">24</option>
        <option value="25">25</option>
        <option value="26">26</option>
        <option value="27">27</option>
        <option value="28">28</option>
        <option value="29">29</option>
        <option value="30">30</option>
        <option value="31">31</option>
      </select>
        <!--    Copyright 2004 BMC Software Inc.  --></td>
    </tr>
     <!--<tr>
     <td><img src="images/1ptTrans.gif" alt="" width="40" height="5" /></td>
      <td width="10%" nowrap="nowrap"><input onclick="selectRadio('monthlyRadio')" name="subMode" value="MONTHLY_ADVANCED" type="radio" />
        The </td>
      <td nowrap="nowrap"><select name="monthlyDayOfWeekPrefix">
        <option selected="selected" value="1">First</option>
        <option value="2">Second</option>
        <option value="3">Third</option>
        <option value="4">Fourth</option>
        <option value="5">Fifth</option>
        <option value="6">Last</option>
      </select>
        <select id="monthlyDayOfWeekSelect" size="1" name="monthlyDayOfWeek">
          <option value="1">Sunday</option>
          <option value="2">Monday</option>
          <option value="3">Tuesday</option>
          <option selected="selected" value="4">Wednesday</option>
          <option value="5">Thursday</option>
          <option value="6">Friday</option>
          <option value="7">Saturday</option>
        </select>
        of every
        <select size="1" name="monthlyInterval">
          <option selected="selected" value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option value="6">6</option>
          <option value="7">7</option>
          <option value="8">8</option>
          <option value="9">9</option>
          <option value="10">10</option>
          <option value="11">11</option>
        </select>
        months </td>
    </tr> !-->
    <tr>
      <td colspan="3"><input id="yearlyRadio" onclick="setStartTimeDisabled(false);" name="mode" value="YEARLY" type="radio" />
        <strong>Yearly - repeats every year</strong> <br /></td>
    </tr>
    <tr>
      <td><img src="images/1ptTrans.gif" alt="" width="40" height="5" /></td>
      <td width="10%" nowrap="nowrap"><input onclick="selectRadio('yearlyRadio')" name="subMode" value="YEARLY_BASIC" type="radio" />
        Every </td>
      <td><select id="yearlyMonthSelect" size="1" name="YstartMonth">
        <option value="0">January</option>
        <option value="1">February</option>
        <option value="2">March</option>
        <option value="3">April</option>
        <option value="4">May</option>
        <option value="5">June</option>
        <option selected="selected" value="6">July</option>
        <option value="7">August</option>
        <option value="8">September</option>
        <option value="9">October</option>
        <option value="10">November</option>
        <option value="11">December</option>
      </select>
        <select id="yearlyDayOfMonthSelect" size="1" name="YstartDay">
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option selected="selected" value="6">6</option>
          <option value="7">7</option>
          <option value="8">8</option>
          <option value="9">9</option>
          <option value="10">10</option>
          <option value="11">11</option>
          <option value="12">12</option>
          <option value="13">13</option>
          <option value="14">14</option>
          <option value="15">15</option>
          <option value="16">16</option>
          <option value="17">17</option>
          <option value="18">18</option>
          <option value="19">19</option>
          <option value="20">20</option>
          <option value="21">21</option>
          <option value="22">22</option>
          <option value="23">23</option>
          <option value="24">24</option>
          <option value="25">25</option>
          <option value="26">26</option>
          <option value="27">27</option>
          <option value="28">28</option>
          <option value="29">29</option>
          <option value="30">30</option>
          <option value="31">31</option>
        </select></td>
    </tr>
  <!--  <tr>
      <td><img src="images/1ptTrans.gif" alt="" width="40" height="5" /></td>
      <td width="10%" nowrap="nowrap"><input onclick="selectRadio('yearlyRadio')" name="subMode" value="YEARLY_ADVANCED" type="radio" />
        The </td>
      <td><select name="yearlyDayOfWeekPrefix">
        <option selected="selected" value="1">First</option>
        <option value="2">Second</option>
        <option value="3">Third</option>
        <option value="4">Fourth</option>
        <option value="5">Fifth</option>
        <option value="6">Last</option>
      </select>
        <select id="yearlyDayOfWeekSelect" size="1" name="yearlyDayOfWeek">
          <option value="1">Sunday</option>
          <option value="2">Monday</option>
          <option value="3">Tuesday</option>
          <option selected="selected" value="4">Wednesday</option>
          <option value="5">Thursday</option>
          <option value="6">Friday</option>
          <option value="7">Saturday</option>
        </select>
        of
        <select id="yearlyAdvancedMonthSelect" size="1" name="startMonth">
          <option value="0">January</option>
          <option value="1">February</option>
          <option value="2">March</option>
          <option value="3">April</option>
          <option value="4">May</option>
          <option value="5">June</option>
          <option selected="selected" value="6">July</option>
          <option value="7">August</option>
          <option value="8">September</option>
          <option value="9">October</option>
          <option value="10">November</option>
          <option value="11">December</option>
        </select></td>
    </tr>-->
  </tbody>
</table>
<input type="submit" name="submit" value="Submit" />
</form>
<?php bottomOpg(); ?>
</body>
</html>
