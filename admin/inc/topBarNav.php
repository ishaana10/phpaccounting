<style>
  .user-img{
        position: absolute;
        height: 27px;
        width: 27px;
        object-fit: cover;
        left: -7%;
        top: -12%;
  }
  .btn-rounded{
        border-radius: 50px;
  }
</style>
<!-- Navbar -->
      <nav class="main-header navbar navbar-expand navbar-light text-sm shadow-sm">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
          </li>
          <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo base_url ?>" class="nav-link"><b><?php echo (!isMobileDevice()) ? $_settings->info('name'):$_settings->info('short_name'); ?> - Admin</b></a>
          </li>
        </ul>
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto align-items-center">
          <!-- Tenant Switcher Dropdown -->
          <li class="nav-item mr-2">
            <div class="input-group input-group-sm">
              <div class="input-group-prepend">
                <span class="input-group-text bg-primary text-white border-0"><i class="fas fa-building mr-1"></i> Tenant:</span>
              </div>
              <select id="tenant-switcher" class="form-control form-control-sm border-primary" style="max-width: 220px;">
                <?php
                $active_tenant = $_settings->active_tenant_id();
                $tenants_qry = $conn->query("SELECT * FROM `tenants` WHERE delete_flag = 0 AND status = 1 ORDER BY name ASC");
                while($trow = $tenants_qry->fetch_assoc()):
                ?>
                <option value="<?php echo $trow['id'] ?>" <?php echo $active_tenant == $trow['id'] ? 'selected' : '' ?>><?php echo $trow['name'] ?></option>
                <?php endwhile; ?>
              </select>
            </div>
          </li>
          <!-- <li class="nav-item">
            <a class="nav-link" data-widget="navbar-search" href="#" role="button">
            <i class="fas fa-search"></i>
            </a>
            <div class="navbar-search-block">
              <form class="form-inline">
                <div class="input-group input-group-sm">
                  <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                  <div class="input-group-append">
                    <button class="btn btn-navbar" type="submit">
                    <i class="fas fa-search"></i>
                    </button>
                    <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                    <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </li> -->
          <!-- Messages Dropdown Menu -->
          <li class="nav-item">
            <div class="btn-group nav-link">
                  <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    <span><img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="img-circle elevation-2 user-img" alt="User Image"></span>
                    <span class="ml-3"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
                    <span class="sr-only">Toggle Dropdown</span>
                  </button>
                  <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="<?php echo base_url.'admin/?page=user' ?>"><span class="fa fa-user"></span> My Account</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo base_url.'/classes/Login.php?f=logout' ?>"><span class="fas fa-sign-out-alt"></span> Logout</a>
                  </div>
              </div>
          </li>
          <li class="nav-item">
            
          </li>
         <!--  <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
            <i class="fas fa-th-large"></i>
            </a>
          </li> -->
        </ul>
      </nav>
      <!-- /.navbar -->
      <script>
        $(document).ready(function(){
          $('#tenant-switcher').change(function(){
            var tenant_id = $(this).val();
            start_loader();
            $.ajax({
              url: _base_url_ + 'classes/SystemSettings.php?f=switch_tenant',
              method: 'POST',
              data: { tenant_id: tenant_id },
              dataType: 'json',
              success: function(resp){
                if(resp.status == 'success'){
                  location.reload();
                } else {
                  alert_toast('Failed to switch tenant.', 'error');
                  end_loader();
                }
              },
              error: function(err){
                console.log(err);
                alert_toast('An error occurred.', 'error');
                end_loader();
              }
            });
          });
        });
      </script>