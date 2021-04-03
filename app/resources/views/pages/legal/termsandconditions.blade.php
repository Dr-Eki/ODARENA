@extends('layouts.master')

@section('page-header', 'Terms and Conditions')

@section('content')

<style>

ol.tc
{
  counter-reset: item;
}

ol.tc li
{
  display: block;
  line-height: 1.5;
}

ol.tc li:before
{
  content: counters(item, ".") " ";
  counter-increment: item;
}

</style>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"> Terms and Conditions</h3>
    </div>
    <div class="box-body">

      <p>Last updated: January 11, 2020</p>

      <p>Please read these Terms and Conditions ("Terms", "Terms and Conditions") carefully before using the {{ $website_url }} website (the "Service") operated by {{ $website_name }} ("us", "we", or "our").</p>

      <p>Your access to and use of the Service is conditioned on your acceptance of and compliance with these Terms. These Terms apply to all visitors, users and others who access or use the Service.</p>

      <p>By accessing or using the Service you agree to be bound by these Terms. If you disagree with any part of the terms then you may not access the Service. The Terms and Conditions agreement  for {{ $website_name }} has been created with the help of <a href="https://www.termsfeed.com/">TermsFeed</a>.</p>

      <ol class="tc">
        <li>Source Code
          <ol class="tc">
            <li>The ODARENA source code is freely available and subject to GNU Affero General Public License v3.0, which can be found at the following URL: https://www.gnu.org/licenses/agpl-3.0.en.html</li>
            <li>Nothing in these Terms and Conditions shall be construed to impose any restriction on the aforementioned GNU Affero General Public License v3.0 to the source code.</li>
          </ol>
        </li>
        <li>Accounts
          <ol class="tc">
            <li>You must be at least 18 years or of legal age to register an account and play the game.</li>
            <li>When you create an account with us, you must provide us information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service.</li>
            <li>You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password, whether your password is with our Service or a third-party service.</li>
            <li>You agree not to disclose your password to any third party. If you have any reason to believe your password is not safe, you are required to immediately change it.</li>
            <li>Your username must not be offensive or misleading. This includes profanity, slurs, and names which may cause confusion.</li>
            <li>Accounts are personal and must not be shared. One person per account: only one person is permitted to make use of an account.</li>
            <li>If your account is suspended or banned, you are not permitted to open a new account or use another account.</li>
            <li>If you have forgot your password, use the password reset function. If you do not have access to your email account, contact an administrator on Discord or via {{ $contact_email }}.</li>
          </ol>
        </li>
        <li>Game Rules
          <ol class="tc">
            <li>You are only allowed to have one dominion per round per account.</li>
            <li>You are allowed to have multiple accounts and as such have multiple dominions per round, provided that each dominion is played with equal and reasonable care and effort and that they are all in the same realm. This means they must be of the same alignment. If one is independent and randomly placed in a hostile realm, you must immediately notify us and instruct us to move the dominion to the correct realm.</li>
            <li>Your dominion name or ruler name must not be offensive, abusive, damaging, or misleading.</li>
            <li>Your dominion name must be unique and not similar enough to another dominion so as to cause confusion. If two names are found to be too similar, the latter one may be changed to a new name provided by the player or to one decided by the administrator.</li>
            <li>You must not use any tools, software, application, scripts, or otherwise to take any automatic, scripted, preprogrammed, or similarly automated actions in the game.</li>
            <li>You must not in any way cooperate with another realm or dominions in other realms, such as Non-Aggression Pacts (“NAP”), alliances, or sharing game information.</li>
            <li>You must not in any way intentionally take any action which directly or indirectly benefits another realm or a dominion in another realm.</li>
            <li>You must at all times refrain from using excessive profanity or abusive, offensive language. Banter and smack talk are allowed; just keep it mostly civil.</li>
            <li>You must not exploit any bugs in the game. A bug is a feature, mechanic, logic, or other part of the game which is not working as intended or not working at all. You are expected to have a reasonable understanding of how the game works and we will not accept ignorance as an excuse for exploiting a bug. If you find a bug, please report it immediately in Discord or by contacting an administrator.</li>
            <li>If you are negatively impacted by a bug and cannot be reasonably expected to have known about the bug, you may be compensated appropriately, at our sole discretion.</li>
            <li>If you have been negatively impacted by other players breaching the rules and this can be substantiated during the investigation, you may receive an adequate and proportionate compensation, at our sole discretion.</li>
          </ol>
        </li>
        <li>Links To Other Web Sites
          <ol class="tc">
            <li>Our Service may contain links to third-party web sites or services that are not owned or controlled by {{ $website_name }}.</li>
            <li>{{ $website_name }} has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third party web sites or services. You further acknowledge and agree that {{ $website_name }} shall not be responsible or liable, directly or indirectly, for any damage or loss caused or alleged to be caused by or in connection with use of or reliance on any such content, goods or services available on or through any such web sites or services.</li>
            <li>We strongly advise you to read the terms and conditions and privacy policies of any third-party web sites or services that you visit.</li>
          </ol>
        </li>
        <li>Termination
          <ol class="tc">
            <li>We may terminate or suspend, limit, and/or restrict access to our Service immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</li>
            <li>All provisions of the Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity and limitations of liability.</li>
            <li>We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms</li>
            <li>Upon termination, your right to use the Service will immediately cease. If you wish to terminate your account, you may simply discontinue using the Service.</li>
            <li>All provisions of the Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity and limitations of liability.</li>
            <li>Subitem</li>
          </ol>
        </li>
        <li>Governing Law
          <ol class="tc">
            <li>These Terms shall be governed and construed in accordance with the laws of {{ $company_jurisdiction }}, without regard to its conflict of law provisions.</li>
            <li>Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights. If any provision of these Terms is held to be invalid or unenforceable by a court, the remaining provisions of these Terms will remain in effect. These Terms constitute the entire agreement between us regarding our Service, and supersede and replace any prior agreements we might have between us regarding the Service.</li>
          </ol>
        </li>
        <li>Changes
          <ol class="tc">
            <li>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material we will try to provide at least 30 days notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.</li>
            <li>By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, please stop using the Service.</li>
          </ol>
        </li>
        <li>Privacy Policy
          <ol class="tc">
            <li>These Terms also include the {{ $website_name }} Privacy Policy, available from this link: {{ route('legal.privacypolicy') }}</li>
          </ol>
        </li>
        <li>Contact Us
          <ol class="tc">
            <li>If you have any questions about these Terms, please contact us via email by {{ $contact_email }}.</li>
          </ol>
        </li>
        <li>General
          <ol class="tc">
            <li>If you do not agree with these Terms, you may not create an account.</li>
            <li>If you have already created an account and do not agree with the Terms, you must immediately cease taking part of {{ $website_url }}.</li>
            <li>Insofar as applicable, these Terms also apply to other chat rooms, discussion boards, and other websites directly associated with {{ $website_name }}.</li>
            <li>We may send emails to you related to the game including game event notifications configured by you and announcements. You can unsubscribe from these emails by clicking the unsubscribe link or change your notification settins by logging in, or by contacting {{ $contact_email }}.</li>
          </ol>
        </li>
      </ol>

    </div>
@endsection
