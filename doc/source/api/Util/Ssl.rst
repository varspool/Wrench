-----------------
Wrench\\Util\\Ssl
-----------------

.. php:namespace: Wrench\\Util

.. php:class:: Ssl

    .. php:method:: generatePemFile($pem_file, $pem_passphrase, $country_name, $state_or_province_name, $locality_name, $organization_name, $organizational_unit_name, $common_name, $email_address)

        Generates a new PEM File given the informations

        :type $pem_file: string
        :param $pem_file: the path of the PEM file to create
        :type $pem_passphrase: string
        :param $pem_passphrase: the passphrase to protect the PEM file or if you don't want to use a passphrase
        :type $country_name: string
        :param $country_name: the country code of the new PEM file. e.g.: EN
        :type $state_or_province_name: string
        :param $state_or_province_name: the state or province name of the new PEM file
        :type $locality_name: string
        :param $locality_name: the name of the locality
        :param $organization_name:
        :param $organizational_unit_name:
        :param $common_name:
        :type $email_address: string
        :param $email_address: the email address
