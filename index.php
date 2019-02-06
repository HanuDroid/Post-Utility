<?php
?>
<!DOCTYPE html>
<html lang="en">

<head>

	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
	
	<title>Post Utility</title>
	
	<!-- Bootstrap Core CSS -->
    <!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
	
	<!-- Font Awesome CSS -->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">

	<link rel="stylesheet" href="post_utility.css">
	
</head>

<body>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
	
    <!-- Bootstrap Core JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
	
	<script src="jokes_utility.js"></script>
	
	<div class="container" id="wrapper">
	
		<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="navbar-header">
				<a class="navbar-brand" href="/">Post Utility</a>
			</div>
		</div>
		</nav>
		
		<br><br>
			
		<div class="panel panel-default">
		  <div class="panel-heading">Welcome ! Manage your content here</div>
		  
		  <div class="panel-body">
			
			<div class="row">
			
				<div class="col-md-8">
				
					<div class="input-group">
					  <span class="input-group-addon">Your secret Key</span>
					  <input type="password" class="form-control" id="secret-key">
					</div>
					
					<br>
					
					<div class="btn-group" role="group" aria-label="...">
					  <button type="button" class="btn btn-default" id="new-jokes">Show New Posts</button>
					  <button type="button" class="btn btn-success" id="approved-jokes">Show Approved Posts</button>
					  <button type="button" class="btn btn-danger" id="rejected-jokes">Show Rejected Posts</button>
					  <button type="button" class="btn btn-warning" id="pending-jokes">Show Voting On</button>
					</div>
					
					<br><br>
					
					<div class="btn-group" role="group" aria-label="...">
					  <button type="button" class="btn btn-danger" id="delete-rejected-jokes">Delete Rejected Posts</button>
					  <button type="button" class="btn btn-warning" id="validate-jokes">Validate Posts</button>
					</div>
					
					<br><br>
					
					<div class="input-group">
					  <span class="input-group-addon">Posts Key</span>
					  <input type="text" class="form-control" id="joke-key">
					  <span class="input-group-btn">
						<button type="button" class="btn btn-info" id="show-joke">Show Posts</button>
						<button type="button" class="btn btn-default" id="notify-admin">Notify Admin</button>
						<button type="button" class="btn btn-success" id="approve-joke">Approve Posts</button>
						<button type="button" class="btn btn-danger" id="reject-joke">Reject Posts</button>
					  </span>
					</div>
					
					<br>
					
					<div class="input-group">
					  <span class="input-group-addon">Moderator ID</span>
					  <input type="text" class="form-control" id="moderator-id">
					  <span class="input-group-btn">
						<button type="button" class="btn btn-info" id="pending-by-moderator">Show Pending Posts</button>
					  </span>
					</div>

					<br>
					
					<div class="input-group">
					  <span class="input-group-addon">Post Tag</span>
					  <input type="text" class="form-control" id="post-tag">
					  <span class="input-group-btn">
						<button type="button" class="btn btn-info" id="recycle-post">Recycle Post</button>
					  </span>
					</div>

					<br>

					<div class="input-group">
					  <span class="input-group-addon">Post ID</span>
					  <input type="text" class="form-control" id="post-id">
					  <span class="input-group-btn">
					  	<button type="button" class="btn btn-default" id="add-tag-post-id">Add tag to Post</button>
						<button type="button" class="btn btn-info" id="recycle-post">Recycle Post</button>
					  </span>
					</div>

					<br>
					<button type="button" class="btn btn-danger" id="clear-result-area">Clear Result Area</button>
					
				</div>
			
			</div>
			
		  </div>
		  
		  <div class="panel-footer">
		  
			<div id="result"></div>
			
			<div class="table-responsive">
			<table class="table table-striped" id="jokes_table" style="width: 100%;">
			
				<thead>
				<tr>
					<th>Post Key</th>
					<th>Title</th>
					<th>Content</th>
					<th>Name</th>
					<th>Date</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
			
			</table>
			</div>

			<div id="snackbar">Post Moved to another blog..</div>
		  
		  </div>
		  
		</div>
	
	</div>

</body>
