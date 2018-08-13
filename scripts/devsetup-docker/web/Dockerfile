# Note that all other arguments must go below FROM!
ARG WEB_IMAGE=drunomics/lamp
ARG WEB_IMAGE_VERSION=latest

FROM $WEB_IMAGE:$WEB_IMAGE_VERSION
MAINTAINER drunomics GmbH <hello@drunomics.com>

# Not that the argument defaults are overrriden by default in docker-compose.yml
# and environment variabels provided via .env or the environment.
ARG WEB_DIRECTORY=web
ARG WEB_PROVISION_VARS_PATTERN=web/*-vars.yml
ARG WEB_PROVISION_PLAYBOOK_COMMAND="ansible-playbook lamp.yml --tags tools --skip-tags=apache -vv"
ARG WEB_EXTRA_PLAYBOOK_PATTERN=extra-*.yml
ARG WEB_EXTRA_PLAYBOOK_COMMAND="ansible-playbook extra-play*.yml --skip-tags=php -vv"

# Apply provision vars.
COPY $WEB_PROVISION_VARS_PATTERN /etc/ansible/group_vars/all/

RUN \
  # Fix issues with connecting to upstart, see
  # https://github.com/docker/docker/issues/1024
  mv /sbin/initctl /sbin/initctl.distrib && ln -s /bin/true /sbin/initctl && \
  # Run the playbook.
  cd /root/playbooks && $WEB_PROVISION_PLAYBOOK_COMMAND && \
  # Restore initctl.
  rm /sbin/initctl && cp /sbin/initctl.distrib /sbin/initctl

# Add extra playbooks. Note that docker requires a file for copying to be
# existing, thus always copy .env so it's happy.
COPY $WEB_EXTRA_PLAYBOOK_PATTERN .env* /root/playbooks/

RUN cd /root/playbooks && \
  ([ ! -f extra-requirements.yml ] || ansible-galaxy install -r extra-requirements.yml --force) && \
  # Fix issues with connecting to upstart, see
  # https://github.com/docker/docker/issues/1024
  mv /sbin/initctl /sbin/initctl.distrib && ln -s /bin/true /sbin/initctl && \
  # Run the extra playbook if there is any.
  cd /root/playbooks && \
  ([ ! -f extra-play*.yml ] || $WEB_EXTRA_PLAYBOOK_COMMAND) && \
  # Restore initctl.
  rm /sbin/initctl && cp /sbin/initctl.distrib /sbin/initctl

# Prepare web root.
RUN mkdir -p /srv/default && ln -sf vcs/$WEB_DIRECTORY /srv/default/web
