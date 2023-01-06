@extends('layouts.master')

@section('page-header', 'Privacy Policy')
@section('title', "Privacy Policy")

@section('content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Privacy Policy</h3>
    </div>
    <div class="box-body">
      <p>Last updated: October 03, 2019</p>

      <p>At {{ $website_name }}, accessible from {{ $website_url }}, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by {{ $website_name }} and how we use it.</p>

      <p>If you have additional questions or require more information about our Privacy Policy, do not hesitate to contact us through email at {{ $contact_email }}</p>

      <h3>General Data Protection Regulation (GDPR)</h3>

      <p>We are a Data Controller of your information.</p>

      <p>{{ $company_name }} legal basis for collecting and using the personal information described in this Privacy Policy depends on the Personal Information we collect and the specific context in which we collect the information:</p>
      <ul>
          <li>{{ $company_name }} needs to perform a contract with you</li>
          <li>You have given {{ $company_name }} permission to do so</li>
          <li>Processing your personal information is in {{ $company_name }} legitimate interests</li>
          <li>{{ $company_name }} needs to comply with the law</li>
      </ul>

      <p>{{ $company_name }} will retain your personal information only for as long as is necessary for the purposes set out in this Privacy Policy. We will retain and use your information to the extent necessary to comply with our legal obligations, resolve disputes, and enforce our policies. Our Privacy Policy was generated with the help of <a href="https://www.gdprprivacynotice.com/" target="_new" rel="nofollow">GDPR Privacy Policy Generator</a>.</p>

      <p>If you are a resident of the European Economic Area (EEA), you have certain data protection rights. If you wish to be informed what Personal Information we hold about you and if you want it to be removed from our systems, please contact us.</p>

      <p>In certain circumstances, you have the following data protection rights:</p>

      <ul>
          <li>The right to access, update or to delete the information we have on you.</li>
          <li>The right of rectification.</li>
          <li>The right to object.</li>
          <li>The right of restriction.</li>
          <li>The right to data portability</li>
          <li>The right to withdraw consent</li>
      </ul>

      <h3>What data do we collect?</h3>

      <p>We collect the following data about you:</p>

      <ul>
        <li>Username</li>
        <li>Email address</li>
        <li>Cookies and Usage Data</li>
      </ul>

      <p>"Usage Data" may include information such as your computer’s Internet Protocol address (IP address), browser type, browser version, the pages of our {{ $website_name }} that you visit, the time and date of your visit, the time spent on those pages, unique device identifiers, other diagnostic data, and all game play activities.</p>

      <h3>Log Files</h3>

      <p>{{ $website_name }} follows a standard procedure of using log files. These files log visitors when they visit websites. All hosting companies do this and a part of hosting services' analytics. The information collected by log files include internet protocol (IP) addresses, browser type, Internet Service Provider (ISP), date and time stamp, referring/exit pages, and possibly the number of clicks. These are not linked to any information that is personally identifiable. The purpose of the information is for analyzing trends, administering the site, tracking users' movement on the website, and gathering demographic information.</p>

      <h3>Cookies</h3>

      <p>Like any other website, {{ $website_name }} uses 'cookies'. These cookies are used to store information including visitors' preferences, and the pages on the website that the visitor accessed or visited. The information is used to optimize the users' experience by customizing our web page content based on visitors' browser type and/or other information.</p>

      <h3>Third Party Privacy Policies</h3>

      <p>{{ $website_name }}'s Privacy Policy does not apply to other websites. Thus, we are advising you to consult the respective Privacy Policies of these third-party ad servers for more detailed information. It may include their practices and instructions about how to opt-out of certain options. You may find a complete list of these Privacy Policies and their links here: Privacy Policy Links.</p>

      <p>You can choose to disable cookies through your individual browser options. To know more detailed information about cookie management with specific web browsers, it can be found at the browsers' respective websites. What Are Cookies?</p>

      <h3>Children's Information</h3>

      <p>Only persons aged 18 or older are intended to use this website. Persons aged under 18 are not permitted to open accounts.</p>

      <p>Another part of our priority is adding protection for children while using the internet. We encourage parents and guardians to observe, participate in, and/or monitor and guide their online activity.</p>

      <p>{{ $website_name }} does not knowingly collect any Personal Identifiable Information from children under the age of 18. If you think that your child provided this kind of information on our website, we strongly encourage you to contact us immediately and we will do our best efforts to promptly remove such information from our records.</p>

      <h3>Analytics</h3>

      <p>We may use third party service providers to monitor and analyse the use of {{ $website_name }}.</p>

      <p>Google Analytics is a web analytics service offered by Google that tracks and reports website traffic. Google uses the data collected to track and monitor the use of our Service. This data is shared with other Google services. Google may use the collected data to contextualize and personalize the ads of its own advertising network.</p>

      <p>You can opt-out of having made your activity on the Service available to Google Analytics by installing the Google Analytics opt-out browser add-on. The add-on prevents the Google Analytics JavaScript (ga.js, analytics.js, and dc.js) from sharing information with Google Analytics about visits activity.</p>

      <p>For more information on the privacy practices of Google, please visit the Google Privacy & Terms web page: <a href="https://policies.google.com/privacy?hl=en" rel="nofollow" target="_new">https://policies.google.com/privacy?hl=en</a></p>


      <h3>Online Privacy Policy Only</h3>

      <p>Our Privacy Policy applies only to our online activities and is valid for visitors to our website with regards to the information that they shared and/or collect in {{ $website_name }}. This policy is not applicable to any information collected offline or via channels other than this website.</p>

      <h3>Changes</h3>

      <p>This Privacy Policy may be updated from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p>

      <p>We will notify you via e-mail (if you have given us your e-mail address by creating an account) and/or a notice on {{ $website_url }}, prior to the change becoming effective and update the "Last updated" at the top of this Privacy Policy.</p>

      <h3>Consent</h3>

      <p>By using our website, you hereby consent to our Privacy Policy and agree to its terms.</p>
    </div>
</div>
@endsection
