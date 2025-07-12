<style>
  .sidebar_list li {
    height: 40px !important;
  }

  .sidebar li {
    margin: 1px !important;
  }
</style>
<div class="sidebar" style="background-color: #452C88;">

  <ul class="nav-list pl-0 sidebar_list">

    @if(view_permission('index'))
    <li>
      <a href="{{ route('dashboard') }}" class="{{(request()->routeIs('dashboard')) ? 'menu-acitve' : ''}}">
      <i class="mt-3 ml-3">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" clip-rule="evenodd"
          d="M15.317 0H12.3519C11.115 0 10.1237 1.035 10.1237 2.3049V5.373C10.1237 6.65099 11.115 7.67699 12.3519 7.67699H15.317C16.5452 7.67699 17.5452 6.65099 17.5452 5.373V2.3049C17.5452 1.035 16.5452 0 15.317 0ZM2.22823 1.7589e-05H5.19336C6.43029 1.7589e-05 7.42159 1.03502 7.42159 2.30492V5.37301C7.42159 6.65101 6.43029 7.67701 5.19336 7.67701H2.22823C1.00007 7.67701 0 6.65101 0 5.37301V2.30492C0 1.03502 1.00007 1.7589e-05 2.22823 1.7589e-05ZM2.22823 10.3229H5.19336C6.43029 10.3229 7.42159 11.3498 7.42159 12.6278V15.6959C7.42159 16.9649 6.43029 17.9999 5.19336 17.9999H2.22823C1.00007 17.9999 0 16.9649 0 15.6959V12.6278C0 11.3498 1.00007 10.3229 2.22823 10.3229ZM12.3519 10.3229H15.317C16.5452 10.3229 17.5452 11.3498 17.5452 12.6278V15.6959C17.5452 16.9649 16.5452 17.9999 15.317 17.9999H12.3519C11.115 17.9999 10.1237 16.9649 10.1237 15.6959V12.6278C10.1237 11.3498 11.115 10.3229 12.3519 10.3229Z"
          fill="white" />
        </svg>
      </i>
      <span class="link_name">Dashboard</span>
      </a>
    </li>
  @endif

    <div class="profile text-center mb-2">
      <a href="/logout">
      <div id="logout_icon" class="">
        <button class="btn" style="background-color: #FFFFFF;">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
          d="M13.7907 5.75V3.375C13.7907 2.74511 13.5457 2.14102 13.1096 1.69562C12.6734 1.25022 12.0819 1 11.4651 1H3.32558C2.7088 1 2.11728 1.25022 1.68115 1.69562C1.24502 2.14102 1 2.74511 1 3.375V17.625C1 18.2549 1.24502 18.859 1.68115 19.3044C2.11728 19.7498 2.7088 20 3.32558 20H11.4651C12.0819 20 12.6734 19.7498 13.1096 19.3044C13.5457 18.859 13.7907 18.2549 13.7907 17.625V15.25"
          stroke="#452C88" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M4.72095 10.5H21M21 10.5L17.5116 6.9375M21 10.5L17.5116 14.0625" stroke="#452C88"
          stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        </button>
      </div>
      </a>
    </div>


  </ul>
</div>