<?php include('header.php'); ?>
<script type="text/javascript" src="js/flexigrid.js"></script>
<link href="css/flexigrid.css" rel="stylesheet" media="all" />

		<div id="sub-nav"><div class="page-title">
			<h1>FlexiGrid Tables</h1>
			<span>You can use the breadcrumb as a sub heading if you like ... Below you'll see some tables using the FlexiGrid JQuery Plugin.</span>
		</div>
<?php include('top_buttons.php'); ?></div>
		<div id="page-layout"><div id="page-content">
			<div id="page-content-wrapper">
				<div class="inner-page-title">
					<h2>Example 1</h2>
					<span>The most basic example with the zero configuration, with a table converted into flexigrid</span>
				</div>
				<table id="flex1" style="display:none"></table>
				<br /><br /><br />
				<div class="inner-page-title">
					<h2>Example 2</h2>
					<span>The most basic example with the zero configuration, with a table converted into flexigrid</span>
				</div>
				<table class="flexme1">
					<thead>
				    		<tr>
				            	<th width="100">Col 1</th>
				            	<th width="100">Col 2</th>
				            	<th width="100">Col 3 is a long header name</th>
				            	<th width="300">Col 4</th>
				
				            </tr>
				    </thead>
				    <tbody>
				    		<tr>
				            	<td>This is data 1 with overflowing content</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				            
				    </tbody>
				</table>
<br /><br />
				<div class="inner-page-title">
					<h2>Example 2</h2>
					<span>Table converted into flexigrid with height, and width set to auto, stripes remove.</span>
				</div>
				<table class="flexme2">
					<thead>
				
				    		<tr>
				            	<th width="100">Col 1</th>
				            	<th width="100">Col 2</th>
				            	<th width="100">Col 3 is a long header name</th>
				            	<th width="300">Col 4</th>
				            </tr>
				    </thead>
				
				    <tbody>
				    		<tr>
				            	<td>This is data 1 with overflowing content</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				
				            	<td>This is data 3</td>
				            	<td>This is data 4</td>
				            </tr>
				    		<tr>
				            	<td>This is data 1</td>
				            	<td>This is data 2</td>
				            	<td>This is data 3</td>
				
				            	<td>This is data 4</td>
				            </tr>
				    </tbody>
				</table>

<script type="text/javascript">
			$("#flex1").flexigrid
			(
			{
			url: 'post2.php',
			dataType: 'json',
			colModel : [
				{display: 'ISO', name : 'iso', width : 40, sortable : true, align: 'center'},
				{display: 'Name', name : 'name', width : 180, sortable : true, align: 'left'},
				{display: 'Printable Name', name : 'printable_name', width : 120, sortable : true, align: 'left'},
				{display: 'ISO3', name : 'iso3', width : 130, sortable : true, align: 'left', hide: true},
				{display: 'Number Code', name : 'numcode', width : 80, sortable : true, align: 'right'}
				],
			buttons : [
				{name: 'Add', bclass: 'add', onpress : test},
				{name: 'Delete', bclass: 'delete', onpress : test},
				{separator: true}
				],
			searchitems : [
				{display: 'ISO', name : 'iso'},
				{display: 'Name', name : 'name', isdefault: true}
				],
			sortname: "iso",
			sortorder: "asc",
			usepager: true,
			title: 'Countries',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 700,
			height: 200
			}
			);   

			$('.flexme1').flexigrid();
			$('.flexme2').flexigrid({height:'auto',striped:false});

			function test(com,grid)
						{
							if (com=='Delete')
								{
									confirm('Delete ' + $('.trSelected',grid).length + ' items?')
								}
							else if (com=='Add')
								{
									alert('Add New Item');
								}			
						}


</script>
				<div class="clearfix"></div>
				<?php include('sidebar.php'); ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
<?php include('footer.php'); ?></div>
</body>
</html>