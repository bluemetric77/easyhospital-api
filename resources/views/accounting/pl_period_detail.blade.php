<html>
<head>
	<meta name="viewport" content="width=device-width, user-scalable=no">
    <title>{{$header['title']}}</title>
    <link rel="stylesheet" href="{{public_path('css/document.css')}}" type="text/css" media="all">
	<style type="text/css">
		table, th {
			border: 0.5px solid black;
			border-collapse: collapse;
		}
		table, tr td {
			padding-left: 5px;
			padding-right: 5px;
		}
		table.center {
			margin-left: auto; 
			margin-right: auto;
			font-size: 12px;
		}
	
		.page-break {
			page-break-before: always;
		}
	</style>

</head>
<body>
	<center>
		<h4 class="caption">{{$header['title']}}</h4>
		<h4>{{$header['period']}}</h4>
	</center>
	<br/>
	<table  class="center" cellpadding="4" cellspacing="0">
		<tr>
			<th>Nama Perkiraan</th>
			<th>{{$header['period']}}</th>
			<th>s/d {{$header['period']}}</th>
		</tr>
		<tbody>
			@php 
			  $summary1=0;$summary2=0;
			  $total1=0;$total2=0;
			  $saldo1=0;$saldo2=0;
			  $name='';$name_main='';$printed=false; $printed_main=false;$main_enumdrcr=''
			@endphp
			@foreach($profitloss as $p)
				@if(($printed_main==true) and ($p->level_account==1))
					<tr>
						<td style="width: 500px; padding-left: 1em"><strong>Total {{$name}}<strong></td>
						<td align="right"><strong>{{number_format(floatval($saldo1),2,',','.')}}</strong></td>
						<td align="right"><strong>{{number_format(floatval($saldo2),2,',','.')}}</strong></td>
					</tr>
					<tr>
						<td style="width: 500px;color:red" ><strong>TOTAL {{$name_main}}<strong></td>
						@if (floatval($total1)>=0)
							<td align="right"><strong>{{number_format(floatval($total1),2,',','.')}}</strong></td>
						@else	
							<td align="right" style="color:red"><strong>({{number_format(abs(floatval($total1)),2,',','.')}})</strong></td>
						@endif
						@if (floatval($total2)>=0)
							<td align="right"><strong>{{number_format(floatval($total2),2,',','.')}}</strong></td>
						@else	
							<td align="right" style="color:red"><strong>({{number_format(abs(floatval($total2)),2,',','.')}})</strong></td>
						@endif
					</tr>
					@php $printed_main=false; $printed=false @endphp
				@endif
				@if(($printed==true) and ($p->level_account==2))
					<tr>
						<td style="width: 500px; padding-left: 1em"><strong>Total {{$name}}<strong></td>
						<td align="right"><strong>{{number_format(floatval($saldo1),2,',','.')}}</strong></td>
						<td align="right"><strong>{{number_format(floatval($saldo2),2,',','.')}}</strong></td>
					</tr>
					@php $printed=false @endphp
				@endif
				@php
					if ($p->level_account=='1') {
						$name_main = $p->account_name;
						$saldo1 = 0; $saldo2=0;
						$total1 = 0; $total2=0;
						$printed_main = true;
						$main_enumdrcr=$p->enum_drcr;
					} else if ($p->level_account=='2') {
						$name = $p->account_name;
						$saldo1 = 0;
						$saldo2 = 0;
						$printed = true;
					} else if ($p->level_account>'2') {
						$saldo1 = $saldo1 + floatval($p->reversed1);
						$saldo2 = $saldo2 + floatval($p->reversed2);
					}
				@endphp
				<tr>
					@if($p->level_account =='1')         
						<td style="width: 400px" colspan="3"><strong>{{$p->account_name}}<strong></td>
					@elseif ($p->level_account =='2')         
						<td style="padding-left: 1em" colspan="3"><strong>{{$p->account_name}}<strong></td>
					@elseif ($p->level_account =='3')         
						<td style="padding-left: 2em" colspan="3"><strong>{{$p->account_name}}<strong></td>
					@else
						<td style="padding-left: 3em">{{$p->account_no}} - {{$p->account_name}}</td>
						<td align="right">{{number_format(floatval($p->reversed1),2,',','.')}}</td>
						<td align="right">{{number_format(floatval($p->reversed2),2,',','.')}}</td>
					@endif
				</tr>
				@php
				  if ($main_enumdrcr=='C') {	 
				  	$total1=$total1 + (($p->enum_drcr=='C') ? floatval($p->reversed1) : - floatval($p->reversed1));
				  	$total2=$total2 + (($p->enum_drcr=='C') ? floatval($p->reversed2) : - floatval($p->reversed2));
				  } else if ($main_enumdrcr=='D') {
				  	$total1=$total1 + (($p->enum_drcr=='D') ? floatval($p->reversed1) : - floatval($p->reversed1));
				  	$total2=$total2 + (($p->enum_drcr=='D') ? floatval($p->reversed2) : - floatval($p->reversed2));
				  }
  			  	  $summary1=$summary1 + (($p->enum_drcr=='C') ? floatval($p->reversed1) : - floatval($p->reversed1));
  			  	  $summary2=$summary2 + (($p->enum_drcr=='C') ? floatval($p->reversed2) : - floatval($p->reversed2));
				@endphp
			@endforeach
			<tr>
				<td style="width: 400px; padding-left: 1em"><strong>Total {{$name}}<strong></td>
				<td align="right"><strong>{{number_format(abs(floatval($saldo1)),2,',','.')}}</strong></td>
				<td align="right"><strong>{{number_format(abs(floatval($saldo2)),2,',','.')}}</strong></td>
			</tr>
			<tr>
				<td style="width: 500px;color:red" ><strong>TOTAL {{$name_main}}<strong></td>
				@if (floatval($total1)>=0)
					<td align="right"><strong>{{number_format(floatval($total1),2,',','.')}}</strong></td>
				@else	
					<td align="right" style="color:red"><strong>({{number_format(abs(floatval($total1)),2,',','.')}})</strong></td>
				@endif
				@if (floatval($total2)>=0)
					<td align="right"><strong>{{number_format(floatval($total2),2,',','.')}}</strong></td>
				@else	
					<td align="right" style="color:red"><strong>({{number_format(abs(floatval($total2)),2,',','.')}})</strong></td>
				@endif
			</tr>
			<tr>
				<td style="width: 400px"><strong>TOTAL LABA/RUGI<strong></td>
				@if (floatval($summary1)>=0)
					<td align="right"><strong>{{number_format(floatval($summary1),2,',','.')}}</strong></td>
				@else	
					<td align="right" style="color:red"><strong>({{number_format(abs(floatval($summary1)),2,',','.')}})</strong></td>
				@endif
				@if (floatval($summary2)>=0)
					<td align="right"><strong>{{number_format(floatval($summary2),2,',','.')}}</strong></td>
				@else	
					<td align="right" style="color:red"><strong>({{number_format(abs(floatval($summary2)),2,',','.')}})</strong></td>
				@endif
			</tr>
		</tbody>
	</table>

</body>
</html>
